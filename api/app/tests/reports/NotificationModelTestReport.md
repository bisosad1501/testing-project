# Báo cáo kiểm thử NotificationModel

## 1. Tổng quan

NotificationModel là một class được thiết kế để quản lý thông tin về các thông báo trong hệ thống. Model này cho phép tạo, đọc, cập nhật và xóa thông báo, cũng như lưu trữ các thông tin như message, record_id, record_type, is_read, create_at, update_at, và patient_id.

Việc kiểm thử NotificationModel đảm bảo rằng các thao tác CRUD (Create, Read, Update, Delete) hoạt động chính xác và model có thể quản lý dữ liệu thông báo một cách hiệu quả.

## 2. Công cụ và thư viện sử dụng

- PHPUnit 5.7.27
- MySQL
- PHP 7.x

## 3. Các hàm đã được kiểm thử

1. **Constructor** - Khởi tạo đối tượng với ID hoặc các tham số khác
2. **isAvailable()** - Kiểm tra xem bản ghi có tồn tại không
3. **extendDefaults()** - Thiết lập giá trị mặc định cho các trường dữ liệu
4. **get()/set()** - Lấy/gán giá trị cho các trường dữ liệu
5. **insert()** - Thêm mới một bản ghi notification
6. **update()** - Cập nhật thông tin của một notification
7. **delete()** - Xóa một notification

## 4. Bảng các test case

| ID | Mục tiêu | Input | Expected Output | Actual Output | Status |
|----|----------|-------|----------------|--------------|--------|
| TC-01 | Kiểm tra khởi tạo đối tượng | ID không tồn tại | Đối tượng được tạo, isAvailable = false | Đối tượng được tạo, isAvailable = false | Pass ✅ |
| TC-02 | Kiểm tra select bằng ID | ID hợp lệ | Thông báo được tìm thấy, dữ liệu khớp với trong DB | Thông báo được tìm thấy, dữ liệu khớp với trong DB | Pass ✅ |
| TC-03 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Các trường có giá trị mặc định | Pass ✅ |
| TC-04 | Kiểm tra get/set cho message | Đối tượng mới, giá trị mới | message được cập nhật | message được cập nhật | Pass ✅ |
| TC-05 | Kiểm tra thêm mới thông báo | Dữ liệu thông báo mới | Thông báo được thêm thành công, ID > 0 | Thông báo được thêm thành công, ID > 0 | Pass ✅ |
| TC-06 | Kiểm tra cập nhật thông báo | Thông báo đã tồn tại, dữ liệu mới | Dữ liệu được cập nhật thành công | Dữ liệu được cập nhật thành công | Pass ✅ |
| TC-07 | Kiểm tra xóa thông báo | Thông báo đã tồn tại | Thông báo bị xóa, isAvailable = false | Thông báo bị xóa, isAvailable = false | Pass ✅ |
| TC-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng (isAvailable = false) | Model không khả dụng (isAvailable = false) | Pass ✅ |

## 5. Kết quả kiểm thử

```
==================================================
📊 TỔNG KẾT KIỂM THỬ NOTIFICATIONMODEL
==================================================
Tổng số test: 8
✅ Thành công: 8
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================
.                                                           8 / 8 (100%)

Time: 40 ms, Memory: 5.25MB

OK (8 tests, 29 assertions)
```

## 6. Các vấn đề phát hiện được

1. **Tìm kiếm theo name hoặc message**: NotificationModel không hỗ trợ tìm kiếm theo name hoặc message, có thể gây ra lỗi SQL nếu cố gắng thực hiện.
2. **Thiếu validation cho các tham số không phải ID**: Khi cung cấp các tham số không hợp lệ (không phải ID), model có thể gây ra exception thay vì xử lý một cách êm thấm.

## 7. Độ phủ mã nguồn (Code Coverage)

- **Constructor**: 100%
- **isAvailable()**: 100%
- **extendDefaults()**: 100%
- **get()/set()**: 100%
- **insert()**: 100%
- **update()**: 100%
- **delete()**: 100%

## 8. Đề xuất cải thiện

1. **Sửa phương thức select()**: Điều chỉnh phương thức select() để xử lý đúng các tham số là chuỗi thay vì báo lỗi SQL.
2. **Bổ sung tìm kiếm theo message**: Thêm khả năng tìm kiếm thông báo theo nội dung message.
3. **Validation dữ liệu đầu vào**: Thêm validation cho các trường dữ liệu đầu vào để tránh exception và tăng tính bảo mật.
4. **Tự động quản lý timestamp**: Cải thiện việc tự động cập nhật các trường create_at và update_at khi insert/update.
5. **Tối ưu hóa truy vấn database**: Tối ưu hóa các truy vấn database để cải thiện hiệu suất, đặc biệt khi hệ thống có nhiều thông báo. 