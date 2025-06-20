<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import Sinh Viên từ Excel</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/import_students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="import-container">
                <h2><i class="fas fa-file-import"></i> Import Sinh Viên từ File Excel</h2>
                <form action="ajax/process_import.php" method="POST" enctype="multipart/form-data" class="import-form">
                    <div class="form-group">
                        <label for="excel_file">Chọn File Excel:</label>
                        <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required>
                    </div>
                    <button type="submit" name="import" class="import-btn"><i class="fas fa-upload"></i> Import</button>
                </form>
                <div class="note">
                    <p><strong>Ghi chú:</strong></p>
                    <ul>
                        <li>File Excel phải có định dạng <code>.xlsx</code> hoặc <code>.xls</code>.</li>
                        <li>Cấu trúc cột trong Excel phải tương ứng với cấu trúc của bảng <code>Students</code> và <code>Users</code> trong cơ sở dữ liệu.</li>
                        <li>Ví dụ các cột cần có: <code>student_code</code>, <code>full_name</code>, <code>email</code>, <code>phone</code>, <code>gender</code>, <code>date_of_birth</code>, <code>address</code>, <code>nationality</code>, <code>major</code>, <code>year_of_study</code>, <code>gpa</code>, <code>room_id</code>, <code>status</code>, <code>username</code>, <code>password</code>.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    <?php include 'layout/js.php'; ?>
</body>
</html>
