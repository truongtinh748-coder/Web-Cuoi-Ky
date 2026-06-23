<?php
// Cấu hình kết nối Database
$host = 'localhost';
$db   = 'web-cuoi-ky-main';
$user = 'root';
$pass = ''; // Mặc định của XAMPP thường để trống

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Import Users
    $usersData = json_decode(file_get_contents('users.json'), true);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, email, role) VALUES (?, ?, ?, ?, ?)");
    foreach ($usersData as $u) {
        // Lưu ý: Nên dùng password_hash() thay vì lưu mật khẩu thô trong thực tế
        $stmt->execute([$u['user'], $u['pass'], $u['fullname'], $u['email'], $u['role']]);
    }
    echo "Đã import xong Users.<br>";

    // 2. Import Questions
    $questionsData = json_decode(file_get_contents('questions.json'), true);
    $stmt = $pdo->prepare("INSERT INTO questions (subject_id, exam_code, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($questionsData as $q) {
        $stmt->execute([
            1, // Bạn cần map subjectId sang ID thực tế trong bảng subjects
            $q['examCode'], 
            $q['question'], 
            $q['A'], $q['B'], $q['C'], $q['D'], 
            $q['correct']
        ]);
    }
    echo "Đã import xong Questions.<br>";

    // 3. Import Results
    $resultsData = json_decode(file_get_contents('results.json'), true);
    foreach ($resultsData as $res) {
        // Chèn vào bảng exam_results
        $stmt = $pdo->prepare("INSERT INTO exam_results (user_id, subject_id, score) VALUES (?, ?, ?)");
        $stmt->execute([1, 1, $res['score']]); // Bạn cần map user_id và subject_id thực tế
        $resultId = $pdo->lastInsertId();

        // Chèn vào bảng result_details (nếu có chi tiết)
        if (!empty($res['details'])) {
            $stmtDet = $pdo->prepare("INSERT INTO result_details (result_id, student_answer, is_correct) VALUES (?, ?, ?)");
            foreach ($res['details'] as $det) {
                $stmtDet->execute([$resultId, $det['student_answer'], $det['is_correct']]);
            }
        }
    }
    echo "Đã import xong Results.";

} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}
?>