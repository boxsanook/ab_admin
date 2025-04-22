<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deleteForm">
                <div class="modal-body">
                    <input type="hidden" id="deleteUserId" name="id">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Warning:</strong> You are about to delete this user. This action cannot be undone!
                    </div>
                    <p>This will permanently delete:</p>
                    <ul>
                        <li>User account and personal information</li>
                        <li>Affiliate records if the user is an affiliate</li>
                        <li>Registration tokens associated with this user</li>
                    </ul>
                    <p>Type <strong>DELETE</strong> to confirm:</p>
                    <input type="text" class="form-control" id="confirmDelete" placeholder="Type DELETE here" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="deleteUserBtn" disabled>Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Enable delete button only when "DELETE" is typed
        $('#confirmDelete').on('keyup', function() {
            if ($(this).val() === 'DELETE') {
                $('#deleteUserBtn').prop('disabled', false);
            } else {
                $('#deleteUserBtn').prop('disabled', true);
            }
        });
    });
</script>