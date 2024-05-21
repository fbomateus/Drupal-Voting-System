/**
 * @file
 * JavaScript for the Voting Block.
 */

(function ($, Drupal) {
  if (!$.fn.once) {
    $.fn.once = function (id) {
      return this.filter(function () {
        if (!this.once) {
          this.once = {};
        }
        if (!this.once[id]) {
          this.once[id] = true;
          return true;
        }
        return false;
      });
    };
  }

  Drupal.behaviors.votingBlock = {
    attach: function (context, settings) {
      $('.toggle-answer-options', context).once('votingBlock').click(function () {
        var questionId = $(this).data('question-id');
        $('#answer-options-' + questionId).toggle();
      });

      $('.vote-form', context).once('votingBlock').submit(function (event) {
        event.preventDefault();
        var form = $(this);
        var questionId = form.data('question-id');
        var answerId = form.find('input[name="answer_option"]:checked').val();
        var selectedOption = form.find('input[name="answer_option"]:checked').parent().text().trim();
        var apiKey = '55fe3d788334cbc3faa89fc74ef879d133d6da3b1002edd276a3e83901c7d6be'; // Replace with the actual API key

        if (!answerId) {
          alert(Drupal.t('Please select an answer option.'));
          return;
        }

        $.ajax({
          url: Drupal.url('api/voting/vote'),
          type: 'POST',
          headers: {
            'Authorization': apiKey
          },
          data: JSON.stringify({
            question_id: questionId,
            answer_id: answerId,
            selected_option: selectedOption
          }),
          contentType: 'application/json; charset=utf-8',
          dataType: 'json',
          success: function (response) {
            alert(Drupal.t('Your vote has been submitted.'));
            location.reload();
          },
          error: function (xhr, status, error) {
            alert(Drupal.t('There was an error submitting your vote. Please try again.'));
          }
        });
      });
    }
  };
})(jQuery, Drupal);
