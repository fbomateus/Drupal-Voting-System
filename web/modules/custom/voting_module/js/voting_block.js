/**
 * @file
 * JavaScript for the Voting Block.
 */

(function ($, Drupal) {
  Drupal.behaviors.votingBlock = {
    attach: function (context, settings) {
      $('#question-select', context).once('votingBlock').change(function () {
        var questionId = $(this).val();
        $.ajax({
          url: Drupal.url('api/voting_module/get_answers/' + questionId),
          type: 'GET',
          dataType: 'json',
          success: function (data) {
            var answersContainer = $('#answers-container');
            answersContainer.empty();
            if (data.length) {
              data.forEach(function (answer) {
                answersContainer.append(
                  '<div class="form-check">' +
                  '<input class="form-check-input" type="radio" name="answer_id" id="answer-' + answer.id + '" value="' + answer.id + '">' +
                  '<label class="form-check-label" for="answer-' + answer.id + '">' + answer.label + '</label>' +
                  '</div>'
                );
              });
            } else {
              answersContainer.append('<p>' + Drupal.t('No answers available for this question.') + '</p>');
            }
          },
          error: function () {
            var answersContainer = $('#answers-container');
            answersContainer.empty();
            answersContainer.append('<p>' + Drupal.t('An error occurred while loading the answers. Please try again.') + '</p>');
          }
        });
      }).trigger('change'); // Trigger change event on page load to load answers for the first question.

      // Handle form submission via AJAX
      $('#voting-block-form', context).once('votingBlock').submit(function (event) {
        event.preventDefault();

        var form = $(this);
        var formData = form.serialize();

        $.ajax({
          url: Drupal.url('api/voting_module/vote'),
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
            form[0].reset();
            $('#answers-container').empty();
            if (response.message) {
              form.prepend('<div class="status-message">' + response.message + '</div>');
            }
          },
          error: function (xhr) {
            var errorMessage = Drupal.t('An error occurred while submitting your vote. Please try again.');
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            form.prepend('<div class="error-message">' + errorMessage + '</div>');
          }
        });
      });
    }
  };
})(jQuery, Drupal);
