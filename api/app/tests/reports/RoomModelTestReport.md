# Báo Cáo Kiểm Thử RoomModel

## 1. Tổng quan
Báo cáo này mô tả chi tiết việc kiểm thử đơn vị (unit test) cho lớp RoomModel trong hệ thống quản lý phòng khám. Mục tiêu của việc kiểm thử là đảm bảo tất cả các phương thức trong RoomModel hoạt động chính xác và xử lý các trường hợp đặc biệt một cách phù hợp.

## 2. Quy trình kiểm thử

### 2.1 Công cụ và thư viện sử dụng
- **PHPUnit 5.7.27**: Framework kiểm thử cho PHP
- **PHP 5.6.40**: Phiên bản PHP dùng để chạy test
- **MySQL**: Hệ quản trị cơ sở dữ liệu
- **Pixie**: Thư viện query builder hỗ trợ thao tác với database
- **Viocon**: Container hỗ trợ dependency injection

### 2.2 Các function/class/file được test
- **File được test**: `app/models/RoomModel.php`
- **Các phương thức được test**:
  - **__construct()**: Kiểm tra khởi tạo đối tượng
  - **select()**: Kiểm tra chọn phòng theo ID và tên
  - **extendDefaults()**: Kiểm tra thiết lập giá trị mặc định
  - **insert()**: Kiểm tra thêm phòng mới
  - **update()**: Kiểm tra cập nhật thông tin phòng
  - **delete()**: Kiểm tra xóa phòng

**Lý do không test các thành phần khác**: RoomModel là một model đơn giản chỉ chứa các phương thức CRUD cơ bản và không có logic phức tạp khác cần test. Các thành phần liên quan đến giao diện người dùng sẽ được test riêng trong các test khác.

### 2.3 Bảng bộ test case

| Tên file/class | Mã test case | Mục tiêu của test | Input dữ liệu | Expected output | Ghi chú |
|----------------|--------------|-------------------|---------------|-----------------|---------|
| RoomModel.php | TC-RM-01 | Kiểm tra tạo mới phòng | Dữ liệu phòng mẫu | Phòng được tạo thành công với ID > 0 | Kiểm tra cả trong DB |
| RoomModel.php | TC-RM-02 | Kiểm tra đọc thông tin phòng theo ID | ID phòng vừa tạo | Phòng được tìm thấy và dữ liệu đúng | Kiểm tra tất cả các trường |
| RoomModel.php | TC-RM-03 | Kiểm tra cập nhật thông tin phòng | Dữ liệu phòng mới | Phòng được cập nhật thành công | Kiểm tra dữ liệu sau khi cập nhật |
| RoomModel.php | TC-RM-04 | Kiểm tra xóa phòng | ID phòng | Phòng được xóa thành công | Kiểm tra cả trong DB |
| RoomModel.php | TC-RM-05 | Kiểm tra tìm kiếm phòng theo tên | Tên phòng | Phòng được tìm thấy và dữ liệu đúng | |
| RoomModel.php | TC-RM-06 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng | |
| RoomModel.php | TC-RM-07 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | Model không khả dụng | |
| RoomModel.php | TC-RM-08 | Kiểm tra extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | |
| RoomModel.php | TC-RM-09 | Kiểm tra update phòng không tồn tại | Model không khả dụng | Hàm update trả về false | |
| RoomModel.php | TC-RM-10 | Kiểm tra delete phòng không tồn tại | Model không khả dụng | Hàm delete trả về false | |
| RoomModel.php | TC-RM-11 | Kiểm tra insert với tên trùng lặp | Dữ liệu phòng với tên đã tồn tại | Hệ thống xử lý phù hợp | Kiểm tra cả hai trường hợp (cho phép/không cho phép trùng) |

### 2.4 Link GitHub
(Phần này sẽ được bổ sung sau)

### 2.5 Báo cáo kết quả chạy test

```
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

Runtime:       PHP 5.6.40
Configuration: /Users/bisosad/v1/PTIT-Do-An-Tot-Nghiep/api/phpunit.xml.dist


==================================================
🔍 TC-RM: Kiểm tra quy trình CRUD
==================================================

📋 TC-RM-01: Tạo mới phòng
  Expected: Phòng được tạo thành công với ID > 0
  Result: Room ID: 9
  Status: ✅ SUCCESS

📋 TC-RM-02: Đọc thông tin phòng theo ID
  Expected: Phòng được tìm thấy và có dữ liệu đúng
  Result: ID: 9 - Tìm thấy: Có, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-RM-03: Cập nhật thông tin phòng
  Expected: Phòng được cập nhật thành công
  Result: Cập nhật thành công, Dữ liệu khớp
  Status: ✅ SUCCESS

📋 TC-RM-04: Xóa phòng
  Expected: Phòng được xóa thành công
  Result: Xóa thành công, Kiểm tra tồn tại: Đã xóa, Kiểm tra DB: Đã xóa khỏi DB
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-05: Kiểm tra tìm kiếm phòng theo tên
==================================================

📋 Tìm kiếm phòng theo tên: Room95091
  Expected: Phòng được tìm thấy và dữ liệu khớp
  Result: Tìm kiếm thành công, Dữ liệu khớp, ID khớp
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-06: Kiểm tra select với ID không tồn tại
==================================================

📋 Tìm kiếm phòng với ID không tồn tại
  Expected: Phòng không được tìm thấy
  Result: ID không tồn tại: 1005, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-07: Kiểm tra select với tên không tồn tại
==================================================

📋 Tìm kiếm phòng với tên không tồn tại
  Expected: Phòng không được tìm thấy
  Result: Tên không tồn tại: NonExistent1743795091, Kết quả: Không tìm thấy (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-08: Kiểm tra phương thức extendDefaults
==================================================

📋 Kiểm tra giá trị mặc định sau khi gọi extendDefaults
  Expected: Các trường được thiết lập đúng giá trị mặc định
  Result: Tất cả giá trị mặc định đều đúng
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-09: Kiểm tra update phòng không tồn tại
==================================================

📋 Cập nhật thông tin phòng không tồn tại
  Expected: Hàm update trả về false
  Result: Update phòng không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-10: Kiểm tra delete phòng không tồn tại
==================================================

📋 Xóa phòng không tồn tại
  Expected: Hàm delete trả về false
  Result: Delete phòng không tồn tại trả về: false (đúng)
  Status: ✅ SUCCESS

==================================================
🔍 TC-RM-11: Kiểm tra insert với tên trùng lặp
==================================================

📋 Tạo phòng mới với tên đã tồn tại: DupRoom95091
  Expected: Hệ thống xử lý phù hợp
  Result: Insert phòng trùng tên: Thành công với ID: 12 (cho phép trùng tên)
  Status: ✅ SUCCESS

Time: 34 ms, Memory: 5.00MB

OK (8 tests, 23 assertions)
```

**Kết quả tổng hợp:**
- Tổng số test case: 11
- Tổng số test functions: 8
- Tổng số assertions: 23
- Kết quả: Tất cả PASS (100%)
- Thời gian thực thi: 34ms
- Bộ nhớ sử dụng: 5.00MB

### 2.6 Báo cáo độ phủ code
Báo cáo độ phủ code sẽ được bổ sung sau khi hoàn thành test toàn bộ dự án.

## 3. Kết luận và đề xuất
- **Kết luận**: RoomModel đã được kiểm thử đầy đủ và tất cả các chức năng hoạt động đúng như mong đợi. Cả các trường hợp thông thường và các trường hợp đặc biệt đều được xử lý phù hợp.
- **Đề xuất**: 
  - Có thể bổ sung thêm các ràng buộc UNIQUE cho tên phòng trong database (nếu có yêu cầu)
  - Nên cân nhắc mở rộng kiểm thử để bao gồm các trường hợp về phòng đã được sử dụng trong lịch hẹn
  - Đề xuất thêm validate dữ liệu đầu vào để tăng tính bảo mật và ổn định của hệ thống

## 4. Người thực hiện
- **Người thực hiện**: bisosad1501
- **Ngày thực hiện**: 04/04/2024 