jQuery(document).ready(function ($) {
  const user_ids = Array.from({length: 200}, (_, i) => i + 1);
  const textarea = $('#user-id-log');
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
        let data = response.data;
        if (data.status === 'continue') {
          processed_users++;
          updateProgressBar();
          logUserId(data.old_id, data.new_id);
          updateUser(data.next_user_id);
        } else if (data.status === 'completed') {
          processed_users++;
          updateProgressBar();
          logUserId(data.old_id, data.new_id);
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

  function logUserId(old_id, new_id) {
    let log = textarea.val();
    log += 'Old ID: ' + old_id + ' -> New ID: ' + new_id + '\n';
    textarea.val(log);
    autoScrollTextarea()
  }

  function autoScrollTextarea() {
    textarea.scrollTop(textarea[0].scrollHeight);
  }

  $('#start-update').on('click', function () {
    $(this).prop('disabled', true)
    updateUser(user_ids[0]);
  });
});