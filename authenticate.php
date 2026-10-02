<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/connection.php';

$role = isset($_POST['role']) ? (string)$_POST['role'] : '';

if ($role === 'student') {
    $sr_code = isset($_POST['sr_code']) ? trim((string)$_POST['sr_code']) : '';

    if ($sr_code === '') {
        header('Location: login.php?error=' . urlencode('Please enter your SR-Code.'));
        exit;
    }

    // Check existing student
    $stmt = $pdo->prepare('SELECT id, sr_code, full_name, school_name, is_active FROM students WHERE sr_code = :code OR portal_code = :code2 LIMIT 1');
    $stmt->execute([':code' => $sr_code, ':code2' => $sr_code]);
    $student = $stmt->fetch();

    $newlyCreated = false;

    // Auto-create if not found
    if (!$student) {
        $insert = $pdo->prepare("
            INSERT INTO students (sr_code, portal_code, is_active)
            VALUES (:sr, :portal, 1)
        ");
        $insert->execute([
            ':sr'     => $sr_code,
            ':portal' => $sr_code
        ]);

        $new_id = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, sr_code, full_name, school_name, is_active FROM students WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $new_id]);
        $student = $stmt->fetch();
        $newlyCreated = true;
    }

    if ($student && (int)$student['is_active'] === 1) {
        $_SESSION['student_id']           = (int)$student['id'];
        $_SESSION['student_sr_code']      = (string)$student['sr_code'];
        $_SESSION['student_full_name']    = (string)($student['full_name'] ?? '');
        $_SESSION['student_school_name']  = (string)($student['school_name'] ?? '');
        
        $updateDiag = $pdo->prepare("
            UPDATE water_diagnostics
            SET student_id = ?
            WHERE id = (
                SELECT id FROM water_diagnostics
                ORDER BY id DESC
                LIMIT 1
            )
        ");
        $updateDiag->execute([(int)$student['id']]);

        // New users go straight to profile; existing users go to dashboard
        if ($newlyCreated) {
            header('Location: students/profile.php?new=1');
        } else {
            header('Location: students/dashboard.php');
        }
        exit;
    }

    header('Location: login.php?error=' . urlencode('SR-Code not found or account is inactive.'));
    exit;
}

// --- Admin logic ---
if ($role === 'admin') {
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($password === '') {
        header('Location: admin_login.php?error=' . urlencode('Please enter the admin password.'));
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins LIMIT 10');
    $stmt->execute();
    $admins = $stmt->fetchAll();

    foreach ($admins as $admin) {
        if (password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id']       = (int)$admin['id'];
            $_SESSION['admin_username'] = (string)$admin['username'];
            header('Location: admin/dashboard.php');
            exit;
        }
    }

    header('Location: admin_login.php?error=' . urlencode('Incorrect password.'));
    exit;
}

header('Location: login.php?error=' . urlencode('Invalid request.'));
exit;