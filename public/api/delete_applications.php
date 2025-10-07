<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

<<<<<<< HEAD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';

    if ($status === 'all') {
        // Delete all applications
        $stmt = $conn->prepare("DELETE FROM applications");
        $msg = "All applications have been deleted.";
    } else {
        // Validate status
        if (!in_array($status, ['pending','approved','rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM applications WHERE status = ?");
        $stmt->bind_param("s", $status);
        $msg = ucfirst($status) . " applications removed.";
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete applications.']);
    }

    $stmt->close();
    $conn->close();
=======
// === Helper function to delete a file if it exists ===
function deleteFile(?string $path): void {
    if ($path && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path)) {
        @unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';

    try {
        if ($status === 'all') {
            // Get all pending and rejected applications to delete their files
            $stmt = $conn->prepare("SELECT broker_license_path, prc_license_path, resume_path, valid_id_path, profile_image_path, status FROM applications WHERE status IN ('pending','rejected')");
            $stmt->execute();
            $result = $stmt->get_result();
            $filesToDelete = $result->fetch_all(MYSQLI_ASSOC);

            // Delete the files
            foreach ($filesToDelete as $app) {
                deleteFile($app['broker_license_path']);
                deleteFile($app['prc_license_path']);
                deleteFile($app['resume_path']);
                deleteFile($app['valid_id_path']);
                deleteFile($app['profile_image_path']);
            }

            // Delete all applications
            $stmt = $conn->prepare("DELETE FROM applications");
            $msg = "All applications have been deleted.";
        } else {
            // Validate status
            if (!in_array($status, ['pending','approved','rejected'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }

            // Delete files only if pending or rejected
            if (in_array($status, ['pending','rejected'])) {
                $stmt = $conn->prepare("SELECT broker_license_path, prc_license_path, resume_path, valid_id_path, profile_image_path FROM applications WHERE status = ?");
                $stmt->bind_param("s", $status);
                $stmt->execute();
                $result = $stmt->get_result();
                $filesToDelete = $result->fetch_all(MYSQLI_ASSOC);

                foreach ($filesToDelete as $app) {
                    deleteFile($app['broker_license_path']);
                    deleteFile($app['prc_license_path']);
                    deleteFile($app['resume_path']);
                    deleteFile($app['valid_id_path']);
                    deleteFile($app['profile_image_path']);
                }
            }

            // Delete applications
            $stmt = $conn->prepare("DELETE FROM applications WHERE status = ?");
            $stmt->bind_param("s", $status);
            $msg = ucfirst($status) . " applications removed.";
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete applications.']);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
>>>>>>> origin/ansel
}
