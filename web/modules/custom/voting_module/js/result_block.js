/**
 * @file
 * JavaScript for the Result Block.
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
  
  Drupal.behaviors.resultBlock = {
    attach: function (context, settings) {
      // Assuming the block has a select element for questions
      $('#question-select', context).once('resultBlock').change(function () {
        var questionId = $(this).val();
        $.ajax({
          url: Drupal.url('api/voting_module/results/' + questionId),
          type: 'GET',
          dataType: 'json',
          success: function (data) {
            var resultsContainer = $('#results-container');
            resultsContainer.empty();

            if (data.results.length) {
              var table = $('<table class="table table-striped"><thead><tr><th>' + Drupal.t('Answer Option') + '</th><th>' + Drupal.t('Votes') + '</th><th>' + Drupal.t('Percentage') + '</th><th>' + Drupal.t('Average Rating') + '</th></tr></thead><tbody></tbody></table>');
              var tbody = table.find('tbody');

              data.results.forEach(function (result) {
                var row = $('<tr></tr>');
                row.append('<td>' + result.label + '</td>');
                row.append('<td>' + result.count + '</td>');
                row.append('<td>' + result.percentage.toFixed(2) + '%</td>');
                row.append('<td>' + result.average_rating.toFixed(2) + '</td>');
                tbody.append(row);
              });

              resultsContainer.append(table);
            } else {
              resultsContainer.append('<p>' + Drupal.t('No results available for this question.') + '</p>');
            }
          },
          error: function () {
            var resultsContainer = $('#results-container');
            resultsContainer.empty();
            resultsContainer.append('<p>' + Drupal.t('An error occurred while loading the results. Please try again.') + '</p>');
          }
        });
      }).trigger('change'); // Trigger change event on page load to load results for the first question.
    }
  };
})(jQuery, Drupal);
