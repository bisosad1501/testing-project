# Báo cáo kiểm thử DoctorAndServiceModel

## 1. Tổng quan

Báo cáo này trình bày kết quả kiểm thử model DoctorAndServiceModel, phần quản lý thông tin mối quan hệ giữa bác sĩ và dịch vụ trong hệ thống.

## 2. Chi tiết kiểm thử

### 2.1 Công cụ sử dụng
- PHPUnit 5.7.27
- MySQL (temporary tables)
- PHP 7.x

### 2.2 Các hàm được kiểm thử

| Tên hàm | Mô tả |
|---------|-------|
| `__construct()` | Khởi tạo đối tượng DoctorAndServiceModel, có thể truyền vào ID để select dữ liệu |
| `select()` | Chọn mối quan hệ bác sĩ-dịch vụ theo ID |
| `extendDefaults()` | Thiết lập giá trị mặc định cho các trường khi tạo mới |
| `insert()` | Thêm mới một mối quan hệ bác sĩ-dịch vụ vào database |
| `update()` | Cập nhật thông tin của một mối quan hệ đã tồn tại |
| `delete()` | Xóa một mối quan hệ đã tồn tại |

### 2.3 Bảng test case

| Tên file | Mã test case | Mục tiêu | Input | Expected Output | Ghi chú |
|----------|-------------|----------|-------|----------------|---------|
| DoctorAndServiceModelTest.php | TC-01 | Kiểm tra khởi tạo đối tượng | ID không tồn tại | Đối tượng được tạo, isAvailable = false | Kiểm tra constructor |
| DoctorAndServiceModelTest.php | TC-02 | Kiểm tra select bằng ID | ID hợp lệ | Mối quan hệ được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với ID |
| DoctorAndServiceModelTest.php | TC-03 | Kiểm tra select bằng name | Đối tượng mới tạo | Model không khả dụng (isAvailable = false) | Kiểm tra rằng model không hỗ trợ select bằng name |
| DoctorAndServiceModelTest.php | TC-04 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra phương thức extendDefaults |
| DoctorAndServiceModelTest.php | TC-05 | Kiểm tra thêm mới mối quan hệ | Dữ liệu mối quan hệ mới | Mối quan hệ được thêm thành công, ID > 0 | Kiểm tra phương thức insert |
| DoctorAndServiceModelTest.php | TC-06 | Kiểm tra cập nhật mối quan hệ | Mối quan hệ đã tồn tại, dữ liệu mới | Dữ liệu được cập nhật thành công | Kiểm tra phương thức update |
| DoctorAndServiceModelTest.php | TC-07 | Kiểm tra xóa mối quan hệ | Mối quan hệ đã tồn tại | Mối quan hệ bị xóa, isAvailable = false | Kiểm tra phương thức delete |
| DoctorAndServiceModelTest.php | TC-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng (isAvailable = false) | Kiểm tra xử lý dữ liệu không tồn tại |

### 2.4 Kết quả test

```
==================================================
📊 TỔNG KẾT KIỂM THỬ DOCTORANDSERVICEMODEL
==================================================
Tổng số test: 11
✅ Thành công: 11
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================

Time: 40 ms, Memory: 5.25MB

OK (8 tests, 20 assertions)
```

### 2.5 Vấn đề phát hiện

Trong quá trình kiểm thử, không phát hiện vấn đề nào với DoctorAndServiceModel. Tất cả các phương thức đều hoạt động đúng như mong đợi.

Một số điểm đáng chú ý:
1. **Model không hỗ trợ tìm kiếm theo tên**: DoctorAndServiceModel chỉ hỗ trợ tìm kiếm theo ID, không hỗ trợ tìm kiếm theo tên (name).
2. **Phương thức update trả về đối tượng this**: Tương tự như các model khác, phương thức update() của DoctorAndServiceModel trả về chính đối tượng hiện tại (this), cho phép thực hiện method chaining.
3. **Cấu trúc dữ liệu đơn giản**: Model này chỉ quản lý hai trường chính là service_id và doctor_id, phản ánh mối quan hệ many-to-many giữa bác sĩ và dịch vụ.

### 2.6 Độ phủ code

Các test case đã bao phủ toàn bộ các phương thức chính của DoctorAndServiceModel, bao gồm:
- Khởi tạo đối tượng
- Select dữ liệu theo ID
- Thiết lập giá trị mặc định
- Thêm mới dữ liệu
- Cập nhật dữ liệu
- Xóa dữ liệu

Các trường hợp đặc biệt cũng đã được kiểm thử như:
- Khởi tạo với ID không tồn tại
- Kiểm tra model không hỗ trợ tìm kiếm theo name

### 2.7 Đề xuất cải thiện

- **Validate dữ liệu**: Thêm các kiểm tra hợp lệ của dữ liệu trước khi insert/update, đặc biệt là kiểm tra sự tồn tại của service_id và doctor_id trong các bảng tương ứng.
- **Bổ sung phương thức tìm kiếm**: Có thể thêm các phương thức để tìm kiếm các dịch vụ của một bác sĩ hoặc tìm kiếm các bác sĩ cung cấp một dịch vụ cụ thể.
- **Bảo mật và quyền truy cập**: Xem xét việc bổ sung kiểm tra quyền truy cập để đảm bảo rằng chỉ người dùng có quyền mới có thể thực hiện các thao tác CRUD trên mối quan hệ này. 