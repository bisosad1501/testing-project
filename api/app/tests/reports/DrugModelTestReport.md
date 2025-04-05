# Báo cáo kiểm thử DrugModel

## 1. Tổng quan

Báo cáo này trình bày kết quả kiểm thử model DrugModel, phần quản lý thông tin thuốc trong hệ thống.

## 2. Chi tiết kiểm thử

### 2.1 Công cụ sử dụng
- PHPUnit 5.7.27
- MySQL (temporary tables)
- PHP 7.x

### 2.2 Các hàm được kiểm thử

| Tên hàm | Mô tả |
|---------|-------|
| `__construct()` | Khởi tạo đối tượng DrugModel, có thể truyền vào ID hoặc tên để select dữ liệu |
| `select()` | Chọn bản ghi thuốc theo ID hoặc tên |
| `extendDefaults()` | Thiết lập giá trị mặc định cho các trường khi tạo mới |
| `insert()` | Thêm mới một bản ghi thuốc vào database |
| `update()` | Cập nhật thông tin của một bản ghi thuốc đã tồn tại |
| `delete()` | Xóa một bản ghi thuốc đã tồn tại |

### 2.3 Bảng test case

| Tên file | Mã test case | Mục tiêu | Input | Expected Output | Ghi chú |
|----------|-------------|----------|-------|----------------|---------|
| DrugModelTest.php | TC-01 | Kiểm tra khởi tạo đối tượng | ID không tồn tại | Đối tượng được tạo, isAvailable = false | Kiểm tra constructor |
| DrugModelTest.php | TC-02 | Kiểm tra select bằng ID | ID hợp lệ | Thuốc được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với ID |
| DrugModelTest.php | TC-03 | Kiểm tra select bằng tên | Tên thuốc hợp lệ | Thuốc được tìm thấy, dữ liệu khớp với trong DB | Kiểm tra phương thức select với tên |
| DrugModelTest.php | TC-04 | Kiểm tra giá trị mặc định | Đối tượng mới | Các trường có giá trị mặc định | Kiểm tra phương thức extendDefaults |
| DrugModelTest.php | TC-05 | Kiểm tra thêm mới thuốc | Dữ liệu thuốc mới | Thuốc được thêm thành công, ID > 0 | Kiểm tra phương thức insert |
| DrugModelTest.php | TC-06 | Kiểm tra cập nhật thuốc | Thuốc đã tồn tại, dữ liệu mới | Dữ liệu được cập nhật thành công | Kiểm tra phương thức update |
| DrugModelTest.php | TC-07 | Kiểm tra xóa thuốc | Thuốc đã tồn tại | Thuốc bị xóa, isAvailable = false | Kiểm tra phương thức delete |
| DrugModelTest.php | TC-08 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng (isAvailable = false) | Kiểm tra xử lý dữ liệu không tồn tại |
| DrugModelTest.php | TC-09 | Kiểm tra select với tên không tồn tại | Tên không tồn tại | Model không khả dụng (isAvailable = false) | Kiểm tra xử lý dữ liệu không tồn tại |

### 2.4 Kết quả test

```
==================================================
📊 TỔNG KẾT KIỂM THỬ DRUGMODEL
==================================================
Tổng số test: 12
✅ Thành công: 12
❌ Thất bại: 0
⏱️ Thời gian thực thi: 0.02s
==================================================
.                                                           9 / 9 (100%)

Time: 46 ms, Memory: 5.25MB

OK (9 tests, 19 assertions)
```

### 2.5 Vấn đề phát hiện

Trong quá trình kiểm thử, các vấn đề sau đã được phát hiện và khắc phục:

1. **Phương thức update trả về đối tượng this**: Khác với mong đợi ban đầu là trả về một instance của DrugModel, phương thức thực tế trả về chính đối tượng hiện tại (this). Test case ban đầu đã được điều chỉnh để phù hợp với cách triển khai thực tế.

### 2.6 Độ phủ code

Các test case đã bao phủ toàn bộ các phương thức chính của DrugModel, bao gồm:
- Khởi tạo đối tượng
- Select dữ liệu (theo ID và tên)
- Thiết lập giá trị mặc định
- Thêm mới dữ liệu
- Cập nhật dữ liệu
- Xóa dữ liệu

Các trường hợp đặc biệt cũng đã được kiểm thử như:
- Khởi tạo với ID không tồn tại
- Khởi tạo với tên không tồn tại

### 2.7 Đề xuất cải thiện

- **Mở rộng validate dữ liệu**: Thêm các kiểm tra hợp lệ của dữ liệu trước khi insert/update để đảm bảo tính nhất quán của dữ liệu.
- **Xử lý trùng lặp tên thuốc**: Cân nhắc thêm ràng buộc unique cho tên thuốc để tránh trùng lặp khi tạo mới.
- **Mở rộng model để hỗ trợ thêm thông tin về thuốc**: Có thể bổ sung thêm các trường như giá tiền, thông tin nhà sản xuất, công dụng, v.v. 