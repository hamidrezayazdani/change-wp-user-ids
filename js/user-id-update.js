jQuery(document).ready(function ($) {
  const user_ids = Array.from({length: 200}, (_, i) => i + 1);
  let total_users = user_ids.length;
  let processed_users = 0;

  function updateUser(user_id) {
    $.ajax({
      url: ajax_object.ajax_url,
      type: 'POST',
      data: {
        action: 'update_user_id',
        user_id: user_id
      },
      success: function (response) {
        let data = JSON.parse(response);
        if (data.status === 'continue') {
          processed_users++;
          updateProgressBar();
          updateUser(data.next_user_id);
        } else if (data.status === 'completed') {
          processed_users++;
          updateProgressBar();
          alert('User ID update completed!');
        }
      },
      error: function () {
        alert('An error occurred while updating user IDs.');
      }
    });
  }

  function updateProgressBar() {
    let progress = (processed_users / total_users) * 100;

    $('#progress-bar').css('width', progress + '%');
  }

  $('#start-update').on('click', function () {
    updateUser(user_ids[0]);
  });
});