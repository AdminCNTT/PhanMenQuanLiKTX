<?php
session_start();

// Kiểm tra quyền: chỉ admin/manager/student_manager/accountant
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin','manager','student_manager','accountant'])) {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

// Lấy danh sách đơn rời phòng do hết hạn hợp đồng
$sql = "SELECT dr.departure_id, dr.request_date, dr.reason, dr.status, dr.processed_date, dr.documents, dr.deposit_refund_status,
               s.student_code, s.full_name, c.contract_code, c.deposit AS contract_deposit -- Lấy thêm tiền cọc từ Contracts
        FROM Departure_Requests dr
        JOIN Students s ON dr.student_id = s.student_id
        JOIN Contracts c ON dr.contract_id = c.contract_id
        ORDER BY dr.request_date DESC";

$result = $conn->query($sql);
$departures = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departures[] = $row;
    }
}

// var_dump($departures); // **THÊM DÒNG NÀY ĐỂ DEBUG**

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn rời phòng (Hết hạn HĐ)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung của admin -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/departure_requests_expired.css">
    <style>
    /* Thêm CSS nếu cần thiết để style checkbox và các trường mới trong modal */
    .modal-content .form-group {
        margin-bottom: 15px;
    }
    .modal-content .form-group label {
        display: block; /* Để label trên dòng riêng */
        font-weight: bold;
        margin-bottom: 5px;
    }
    .modal-content .form-group input[type="text"],
    .modal-content .form-group textarea,
    .modal-content .form-group select,
    .modal-content .form-group input[type="number"] { /* Thêm style cho input type number */
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; /* Để padding không làm tăng kích thước phần tử */
    }
     .modal-content .form-group textarea {
        height: 100px; /* Đặt chiều cao cố định cho textarea */
        resize: vertical; /* Cho phép kéo dọc textarea */
    }
    .modal-content .form-group input[readonly] {
        background-color: #f0f0f0; /* Màu nền xám nhạt cho trường read-only */
        cursor: not-allowed; /* Biểu tượng con trỏ "not-allowed" */
    }
    .input-with-unit { /* Style cho div bọc input và đơn vị tiền tệ */
        display: flex;
        align-items: center;
    }
    .input-with-unit input[type="number"] { /* Style riêng cho input number trong div input-with-unit */
        flex-grow: 1; /* Để input number chiếm hết phần còn lại */
    }
    .input-with-unit .unit { /* Style cho span đơn vị tiền tệ */
        margin-left: 5px;
        font-weight: bold;
    }
    .modal-content .btn-group { /* Style cho btn-group */
        text-align: center; /* Canh giữa nút */
        margin-top: 20px;
    }

    .modal-content .btn-group button { /* Style chung cho button trong btn-group */
        padding: 10px 20px;
        margin: 0 5px; /* Khoảng cách giữa 2 nút */
        cursor: pointer;
        border-radius: 5px;
    }

    .modal-content .btn-group .btn-confirm { /* Style nút "Xác nhận" */
         background-color: #007bff;
         color: white;
         border: none;
    }

    .modal-content .btn-group .btn-cancel { /* Style nút "Hủy" */
        background-color: #dc3545; /* Màu đỏ */
        color: white;
        border: none;
    }
    .modal-content .checkbox-group { /* Style cho div bọc checkbox và label */
        display: flex; /* Sử dụng Flexbox */
        align-items: center; /* Canh giữa theo chiều dọc */
        margin-bottom: 15px; /* Thêm margin bottom cho group */
    }

    .modal-content .checkbox-group input[type="checkbox"] { /* Style cho checkbox */
        margin-right: 5px; /* Tạo khoảng cách bên phải checkbox */
    }
</style>
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="container1">
                <h2>Danh sách Đơn rời phòng do Hết hạn Hợp đồng</h2>
                <?php if(count($departures) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Sinh viên</th>
                                <th>Mã SV</th>
                                <th>Mã HĐ</th>
                                <th>Ngày gửi</th>
                                <th>Lý do</th>
                                <th>Tài liệu</th>
                                <th>Trạng thái</th>
                                <th>Ngày xử lý</th>
                                <th>Trạng thái cọc</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($departures as $dep): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dep['departure_id']); ?></td>
                                <td><?php echo htmlspecialchars($dep['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($dep['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($dep['contract_code']); ?></td>
                                <td><?php echo htmlspecialchars($dep['request_date']); ?></td>
                                <td style="word-wrap: break-word; max-width: 200px"><?php echo nl2br(htmlspecialchars($dep['reason'])); ?></td>
                                <td>
                                    <?php
                                        if (is_array($dep['documents'])) {
                                            echo '<div class="file-links">';
                                            foreach ($dep['documents'] as $documentPath) {
                                                $fileName = basename($documentPath);
                                                $fileURL = '/php/student/' . htmlspecialchars($documentPath); // Đảm bảo đường dẫn đúng
                                                echo '<a href="' . $fileURL . '" target="_blank">' . htmlspecialchars($fileName) . '</a>';
                                            }
                                            echo '</div>';
                                        } else {
                                            echo 'Không có';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if($dep['status'] == 'pending') echo 'Chờ xử lý';
                                        elseif($dep['status'] == 'approved') echo 'Đã duyệt';
                                        elseif($dep['status'] == 'rejected') echo 'Từ chối';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($dep['processed_date'] ?? ''); ?></td>
                                <td>
                                    <?php
                                        // Hiển thị Trạng thái Cọc
                                        switch($dep['deposit_refund_status']){
                                            case 'pending_admin_action': echo 'Chờ duyệt cọc'; break;
                                            case 'refund_initiated': echo 'Đã gửi yêu cầu trả cọc'; break;
                                            case 'refund_confirmed_student': echo 'SV đã nhận cọc'; break;
                                            case 'refunded': echo 'Đã hoàn cọc'; break;
                                            default: echo htmlspecialchars($dep['deposit_refund_status']);
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if($dep['status'] == 'pending'): ?>
                                        <button class="action-btn approve-btn" data-id="<?php echo $dep['departure_id']; ?>" data-deposit="<?php echo htmlspecialchars($dep['contract_deposit']); ?>">Duyệt</button>
                                        <button class="action-btn reject-btn" data-id="<?php echo $dep['departure_id']; ?>">Từ chối</button>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Không có đơn rời phòng nào do hết hạn hợp đồng.</p>
                <?php endif; ?>
            </div>
        </main>

    <!-- Modal duyệt đơn -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeApproveModal">×</span>
            <h3>Duyệt đơn rời phòng (Hết hạn HĐ)</h3>
            <p>Chọn xử lý phòng và hợp đồng khi sinh viên rời:</p>

            <!-- Phần hiển thị Tiền cọc gốc (Read-only) -->
            <div class="form-group">
                <label for="deposit_amount_original">Tiền cọc hợp đồng (VND):</label>
                <div class="input-with-unit">
                    <input type="text" id="deposit_amount_original" name="deposit_amount_original" value="" readonly>
                    <span class="unit">VND</span>
                </div>
            </div>

            <!-- Phần Điều chỉnh Tiền cọc (ẩn/hiện) -->
            <div id="refundAmountSection" style="display: none;"> <div class="form-group">
                    <label for="refund_amount">Số tiền cọc hoàn trả (VND):</label>
                    <div class="input-with-unit">
                        <input type="number" id="refund_amount" name="refund_amount" value="" min="0" class="refund-input">
                        <span class="unit">VND</span>
                    </div>
                </div>
                 <!-- Textbox Lý do Điều Chỉnh Cọc -->
                <div class="form-group">
                    <label for="refund_reduction_reason">Lý do điều chỉnh tiền cọc:</label>
                    <textarea id="refund_reduction_reason" name="refund_reduction_reason" placeholder="Nhập lý do nếu giảm hoặc không hoàn trả tiền cọc"></textarea>
                </div>
            </div>

            <!-- Checkbox "Khởi tạo yêu cầu trả cọc" -->
            <div style="display: flex; position: relative;" class="form-group checkbox-group">
                <input style="position: absolute; left: 81px; top: 2px;" type="checkbox" id="initiate_refund" name="initiate_refund" value="1">
                <label for="initiate_refund">Khởi tạo yêu cầu trả cọc</label>
            </div>


            <div class="btn-group">
                <button type="button" class="btn-confirm" id="confirmApprove">Xác nhận</button>
                <!-- <button type="button" class="btn-cancel close-modal" id="closeApproveModal">Hủy</button> -->
            </div>
        </div>
    </div>

    <!-- Modal từ chối đơn -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeRejectModal">×</span>
            <h3>Từ chối đơn rời phòng</h3>
            <div class="form-group">
                <label for="reject_reason">Lý do từ chối:</label>
                <textarea id="reject_reason" rows="4" placeholder="Nhập lý do từ chối"></textarea>
            </div>
            <button class="btn-confirm" id="confirmReject">Xác nhận từ chối</button>
        </div>
    </div>
    <?php include 'layout/js.php'; ?>
    <script src="../../assets/js/departure_requests_expired.php.js"></script>
</body>
</html>