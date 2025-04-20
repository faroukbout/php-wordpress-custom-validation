<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';

    // Get the entry ID from the request
    $entry_id = intval($_POST['entry_id']);

    if ($entry_id > 0) {
        // Start a transaction to ensure both deletions succeed
        mysqli_begin_transaction($conn);

        try {
            // Delete the entry from `wp_frmt_form_entry`
            $query_entry = "DELETE FROM wp_frmt_form_entry WHERE entry_id = ?";
            $stmt_entry = mysqli_prepare($conn, $query_entry);
            mysqli_stmt_bind_param($stmt_entry, 'i', $entry_id);
            $result_entry = mysqli_stmt_execute($stmt_entry);

            // Delete associated data from `wp_frmt_form_entry_meta`
            $query_meta = "DELETE FROM wp_frmt_form_entry_meta WHERE entry_id = ?";
            $stmt_meta = mysqli_prepare($conn, $query_meta);
            mysqli_stmt_bind_param($stmt_meta, 'i', $entry_id);
            $result_meta = mysqli_stmt_execute($stmt_meta);

            if ($result_entry && $result_meta) {
                // Commit the transaction
                mysqli_commit($conn);
                echo json_encode(['status' => 'success', 'message' => 'Row and associated data deleted successfully.']);
            } else {
                // Rollback the transaction in case of failure
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete the row or associated data.']);
            }
        } catch (Exception $e) {
            // Rollback the transaction in case of an exception
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid entry ID.']);
    }

    // Close the database connection
    mysqli_close($conn);
}

?>
