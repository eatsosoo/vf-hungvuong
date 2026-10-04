const english = {
    "Vui lòng chọn ngày và nhập giờ.":
        "Please select a date and enter a time.",
    "Thời gian này không khả dụng. Vui lòng chọn giờ khác.":
        "This time is unavailable. Please choose another time.",
    "Ngày hoặc giờ chưa hợp lệ.":
        "Invalid date or time.",
    "Chưa chọn ngày":
        "No date selected",
    "Chọn từ :date.":
        "Choose a time on or after :date.",
    "Chọn đến :date.":
        "Choose a time on or before :date.",
    " lúc ":
        " at ",
    "Đổi xe":
        "Change vehicle",
    "Xong":
        "Done",
    "Vui lòng kiểm tra lại các thông tin được đánh dấu bên dưới.":
        "Please review the highlighted fields below.",
    "Đang gửi đăng ký…":
        "Sending your booking…",
    "Phiên làm việc đã hết hạn. Vui lòng tải lại trang rồi thử lại.":
        "Your session has expired. Please reload the page and try again.",
    "Bạn đã gửi nhiều yêu cầu. Vui lòng chờ một phút rồi thử lại.":
        "Too many requests. Please wait a minute and try again.",
    "Chưa thể gửi đăng ký. Thông tin của bạn vẫn được giữ lại, vui lòng thử lại.":
        "Unable to submit your booking. Your details have been kept. Please try again.",
    "Chưa tải được ảnh màu này. Vui lòng chọn lại để thử lần nữa.":
        "Unable to load this color. Please select it again to retry.",
    "Đang tải màu :color…":
        "Loading :color…",
};

export function translate(message, replacements = {}) {
    let text = document.documentElement.lang === 'en' ? (english[message] || message) : message;
    Object.entries(replacements).forEach(([key, value]) => { text = text.replaceAll(`:${key}`, value); });
    return text;
}
