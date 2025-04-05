# Báo cáo kiểm thử đơn vị - BookingPhotoModel

## Tổng quan
Tài liệu này trình bày kết quả kiểm thử đơn vị cho lớp `BookingPhotoModel` trong hệ thống quản lý đặt lịch khám. Mục tiêu của việc kiểm thử là đảm bảo tất cả các phương thức của lớp hoạt động chính xác và xử lý đúng các trường hợp đặc biệt.

## Quy trình kiểm thử

### Công cụ và thư viện sử dụng
- PHPUnit 5.7.27
- PHP 5.6.40
- MySQL (qua MAMP)
- Pixie (query builder)
- Viocon (container)

### Các hàm/lớp/file được kiểm thử
- File: `app/models/BookingPhotoModel.php`
- Các phương thức được kiểm thử:
  - `__construct()`
  - `select()`
  - `extendDefaults()`
  - `insert()`
  - `update()`
  - `delete()`

### Bảng trường hợp kiểm thử

| Mã test | Mục tiêu | Dữ liệu đầu vào | Kết quả mong đợi | Ghi chú |
|---------|----------|-----------------|------------------|---------|
| TC-BPM-01 | Kiểm tra tạo mới ảnh đặt lịch | booking_id, url | Ảnh được tạo thành công với ID > 0 | Test thành phần đầu tiên của CRUD |
| TC-BPM-02 | Kiểm tra đọc thông tin ảnh đặt lịch | ID ảnh đã tạo | Thông tin ảnh được trả về chính xác | Test thành phần thứ hai của CRUD |
| TC-BPM-03 | Kiểm tra cập nhật thông tin ảnh đặt lịch | ID ảnh đã tạo, url mới | Thông tin ảnh được cập nhật thành công | Test thành phần thứ ba của CRUD |
| TC-BPM-04 | Kiểm tra xóa ảnh đặt lịch | ID ảnh đã tạo | Ảnh được xóa thành công | Test thành phần thứ tư của CRUD |
| TC-BPM-05 | Kiểm tra select với ID không tồn tại | ID không tồn tại | Model không khả dụng (isAvailable() = false) | Test xử lý trường hợp đặc biệt |
| TC-BPM-06 | Kiểm tra phương thức extendDefaults | Model không có dữ liệu | Các trường được thiết lập giá trị mặc định | Test khởi tạo dữ liệu mặc định |
| TC-BPM-07 | Kiểm tra update ảnh đặt lịch không tồn tại | Model không khả dụng | Phương thức update trả về false | Test xử lý trường hợp đặc biệt |
| TC-BPM-08 | Kiểm tra delete ảnh đặt lịch không tồn tại | Model không khả dụng | Phương thức delete trả về false | Test xử lý trường hợp đặc biệt |
| TC-BPM-09 | Kiểm tra insert khi model đã khả dụng | Model đã khả dụng | Phương thức insert trả về false | Test xử lý trường hợp đặc biệt |
| TC-BPM-10 | Kiểm tra tạo ảnh với booking_id không tồn tại | booking_id không tồn tại | Kiểm tra ràng buộc khóa ngoại | Test ràng buộc với bảng bookings |

## Kết quả kiểm thử

### Tóm tắt kết quả PHPUnit

```
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

Khả năng kiểm thử: 9 test cases
                   7 test functions
                   22 assertions

Kết quả:         ✅ Thành công: 9/9 (100%)
                 ❌ Thất bại: 0/9 (0%)
                 ⏱ Thời gian thực thi: 0.0896 giây
                 👤 Người thực hiện: bisosad1501
```

### Báo cáo độ bao phủ mã nguồn
Báo cáo độ bao phủ mã nguồn sẽ được bổ sung sau khi hoàn thành kiểm thử toàn bộ dự án.

## Kết luận và kiến nghị

Lớp `BookingPhotoModel` đã được kiểm thử kỹ lưỡng, tất cả các chức năng đều hoạt động đúng như mong đợi. Các phương thức CRUD (Create, Read, Update, Delete) đều được kiểm tra và xác nhận hoạt động chính xác. Ngoài ra, các trường hợp đặc biệt như xử lý ID không tồn tại, ràng buộc khóa ngoại, và các giá trị mặc định cũng được kiểm tra đầy đủ.

Một số kiến nghị cho việc cải thiện:

1. **Cải thiện kiểm tra ràng buộc tham chiếu**: Cần đảm bảo ràng buộc khóa ngoại được thực thi nghiêm ngặt trong cơ sở dữ liệu để tránh dữ liệu không nhất quán.
   
2. **Xử lý tệp ảnh**: Nên bổ sung chức năng xử lý tệp ảnh thực sự thay vì chỉ lưu URL. Ví dụ: xác thực loại tệp, kiểm tra kích thước, và lưu trữ tệp vật lý.

3. **Cài đặt bộ ghi nhật ký**: Bổ sung ghi log cho các hoạt động quan trọng như thêm/xóa ảnh để dễ dàng theo dõi và gỡ lỗi.

4. **Xử lý đồng thời**: Cần kiểm tra khả năng xử lý đồng thời để đảm bảo tính nhất quán khi nhiều người dùng tương tác với hệ thống cùng lúc.

## Thông tin tác giả

**Người thực hiện kiểm thử:** B21DCDT205-Lê Đức Thắng  
**Thời gian thực hiện:** Tháng 5, 2023 