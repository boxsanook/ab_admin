$(document).ready(function() {
    // Initialize DataTable
    const table = $("#usersTable").DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "order": [
            [0, "desc"]
        ],
        "language": {
            "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
            "zeroRecords": "ไม่พบข้อมูล",
            "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
            "infoEmpty": "ไม่มีข้อมูล",
            "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
            "search": "ค้นหา:",
            "paginate": {
                "first": "หน้าแรก",
                "last": "หน้าสุดท้าย",
                "next": "ถัดไป",
                "previous": "ก่อนหน้า"
            }
        }
    });

    // Add User Form Submit
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: $(this).serialize() + '&action=insert',
            success: function(response) {
                $('#addUserModal').modal('hide');
                showAlert(response, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function() {
                showAlert('An error occurred while adding the user.', 'error');
            }
        });
    });

    // Edit User
    window.editUser = function(userId) {
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: {
                action: 'edit',
                id: userId
            },
            success: function(response) {
                const user = JSON.parse(response);
                if (user.error) {
                    showAlert(user.error, 'error');
                    return;
                }

                // Populate form fields
                $('#edit_user_id').val(user.id);
                $('#edit_display_name').val(user.display_name);
                $('#edit_email').val(user.email);
                $('#edit_name').val(user.name);
                $('#edit_picture_url').val(user.picture_url);
                $('#edit_active').val(user.active);
                $('#edit_is_admin').val(user.is_admin);
                $('#edit_affiliate').val(user.affiliate);
                $('#edit_computer_id').val(user.Computer_ID);

                $('#editUserModal').modal('show');
            },
            error: function() {
                showAlert('An error occurred while fetching user data.', 'error');
            }
        });
    };

    // Edit User Form Submit
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: $(this).serialize() + '&action=update',
            success: function(response) {
                $('#editUserModal').modal('hide');
                showAlert(response, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function() {
                showAlert('An error occurred while updating the user.', 'error');
            }
        });
    });

    // Delete User
    window.deleteUser = function(userId) {
        $('#delete_user_id').val(userId);
        $('#deleteUserModal').modal('show');
    };

    // Delete User Form Submit
    $('#deleteUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: $(this).serialize() + '&action=delete',
            success: function(response) {
                $('#deleteUserModal').modal('hide');
                showAlert(response, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function() {
                showAlert('An error occurred while deleting the user.', 'error');
            }
        });
    });

    // Copy User
    window.copyUser = function(userId) {
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: {
                action: 'edit',
                id: userId
            },
            success: function(response) {
                const user = JSON.parse(response);
                if (user.error) {
                    showAlert(user.error, 'error');
                    return;
                }

                // Populate form fields
                $('#copy_user_id').val(user.id);
                $('#copy_display_name').val(user.display_name + ' (Copy)');
                $('#copy_email').val(user.email);
                $('#copy_name').val(user.name);
                $('#copy_active').val(user.active);
                $('#copy_is_admin').val(user.is_admin);
                $('#copy_affiliate').val(user.affiliate);
                $('#copy_computer_id').val(user.Computer_ID);

                $('#copyUserModal').modal('show');
            },
            error: function() {
                showAlert('An error occurred while fetching user data.', 'error');
            }
        });
    };

    // Copy User Form Submit
    $('#copyUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: $(this).serialize() + '&action=copy',
            success: function(response) {
                $('#copyUserModal').modal('hide');
                showAlert(response, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function() {
                showAlert('An error occurred while copying the user.', 'error');
            }
        });
    });

    // Clear modal forms when hidden
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
    });
});

// Show alert message
function showAlert(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

    // Remove any existing alerts
    $('.alert').remove();

    // Add new alert before the content
    $('.content-header').after(alertHtml);

    // Auto-hide alert after 3 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}