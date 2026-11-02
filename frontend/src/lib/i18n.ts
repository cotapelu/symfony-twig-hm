// Unified i18n Module - TypeScript Version
// Supports multiple languages with fallback

import { browser } from "$app/environment";
import { writable, derived, get } from "svelte/store";

// ============================================================================
// TYPES
// ============================================================================

export type Locale =
  | "en"
  | "vi"
  | "es"
  | "fr"
  | "zh"
  | "ja"
  | "ko"
  | "de"
  | "pt"
  | "ru"
  | "ar"
  | "he"
  | "fa"
  | "ur";

export interface LocaleConfig {
  code: Locale;
  name: string;
  nativeName: string;
  dir: "ltr" | "rtl";
}

export interface TranslationValue {
  [key: string]: string | number | boolean | null | undefined;
}

export type TranslationMap = {
  [key: string]: string | TranslationValue | TranslationValue[];
};

export interface Translations {
  [locale: string]: TranslationMap;
}

// ============================================================================
// LOCALE CONFIGURATIONS
// ============================================================================

export const SUPPORTED_LOCALES: Record<Locale, LocaleConfig> = {
  en: { code: "en", name: "English", nativeName: "English", dir: "ltr" },
  vi: { code: "vi", name: "Vietnamese", nativeName: "Tiếng Việt", dir: "ltr" },
  es: { code: "es", name: "Spanish", nativeName: "Español", dir: "ltr" },
  fr: { code: "fr", name: "French", nativeName: "Français", dir: "ltr" },
  zh: { code: "zh", name: "Chinese", nativeName: "中文", dir: "ltr" },
  ja: { code: "ja", name: "Japanese", nativeName: "日本語", dir: "ltr" },
  ko: { code: "ko", name: "Korean", nativeName: "한국어", dir: "ltr" },
  de: { code: "de", name: "German", nativeName: "Deutsch", dir: "ltr" },
  pt: { code: "pt", name: "Portuguese", nativeName: "Português", dir: "ltr" },
  ru: { code: "ru", name: "Russian", nativeName: "Русский", dir: "ltr" },
  ar: { code: "ar", name: "Arabic", nativeName: "العربية", dir: "rtl" },
  he: { code: "he", name: "Hebrew", nativeName: "עברית", dir: "rtl" },
  fa: { code: "fa", name: "Persian", nativeName: "فارسی", dir: "rtl" },
  ur: { code: "ur", name: "Urdu", nativeName: "اردو", dir: "rtl" },
};

export const RTL_LOCALES: Locale[] = ["ar", "he", "fa", "ur"];

// ============================================================================
// DEFAULT TRANSLATIONS (Embedded for offline support)
// ============================================================================

const DEFAULT_TRANSLATIONS: Translations = {
  en: {
    // Common
    loading: "Loading...",
    error: "Error",
    save: "Save",
    cancel: "Cancel",
    delete: "Delete",
    edit: "Edit",
    create: "Create",
    search: "Search",
    filter: "Filter",
    retry: "Retry",
    close: "Close",
    ok: "OK",
    yes: "Yes",
    no: "No",
    all: "All",
    none: "None",
    select: "Select",
    deselect: "Deselect",
    required: "Required",
    optional: "Optional",

    // Navigation
    dashboard: "Dashboard",
    messages: "Messages",
    groups: "Groups",
    calls: "Calls",
    files: "Files",
    profile: "Profile",
    settings: "Settings",
    logout: "Logout",
    login: "Login",
    register: "Register",
    home: "Home",
    back: "Back",
    forward: "Forward",
    menu: "Menu",

    // Dashboard
    messages_count: "Messages",
    groups_count: "Groups",
    files_count: "Files",
    calls_count: "Calls",
    no_data: "No data available",
    recent_activity: "Recent Activity",
    quick_actions: "Quick Actions",
    welcome_back: "Welcome back",
    your_stats: "Your Stats",

    // Messages
    send_message: "Send Message",
    message_placeholder: "Type your message...",
    no_messages: "No messages yet",
    unread_messages: "unread",
    typing: "typing...",
    is_typing: "{name} is typing",
    are_typing: "{names} are typing",
    message_sent: "Message sent",
    message_delivered: "Delivered",
    message_read: "Read",
    mark_all_read: "Mark all as read",
    delete_conversation: "Delete conversation",
    mute_notifications: "Mute notifications",
    pin_conversation: "Pin conversation",
    archive_conversation: "Archive conversation",

    // Groups
    create_group: "Create Group",
    group_name: "Group Name",
    group_description: "Group Description",
    join_group: "Join Group",
    leave_group: "Leave Group",
    members: "members",
    member: "member",
    add_members: "Add Members",
    remove_member: "Remove Member",
    admin: "Admin",
    group_admin: "Group Admin",
    group_settings: "Group Settings",
    public_group: "Public Group",
    private_group: "Private Group",

    // Files
    upload_file: "Upload File",
    download: "Download",
    file_size: "Size",
    uploaded_by: "Uploaded by",
    no_files: "No files yet",
    drag_and_drop: "Drag and drop files here",
    browse_files: "Browse Files",
    delete_file: "Delete File",
    share_file: "Share File",
    file_preview: "File Preview",

    // Profile
    personal_info: "Personal Information",
    display_name: "Display Name",
    email: "Email Address",
    bio: "Bio",
    change_avatar: "Change Avatar",
    save_profile: "Save Profile",
    edit_profile: "Edit Profile",
    view_profile: "View Profile",

    // Settings
    appearance: "Appearance",
    theme: "Theme",
    light_theme: "Light",
    dark_theme: "Dark",
    system_theme: "System",
    language: "Language",
    notifications: "Notifications",
    push_notifications: "Push Notifications",
    quiet_hours: "Quiet Hours",
    privacy: "Privacy",
    show_online: "Show Online Status",
    read_receipts: "Read Receipts",
    save_settings: "Save Settings",
    reset_settings: "Reset to Default",

    // Auth
    username: "Username",
    password: "Password",
    confirm_password: "Confirm Password",
    forgot_password: "Forgot Password?",
    remember_me: "Remember Me",
    dont_have_account: "Don't have an account?",
    already_have_account: "Already have an account?",
    sign_up: "Sign Up",
    sign_in: "Sign In",
    sign_out: "Sign Out",

    // Errors
    network_error: "Network error. Please check your connection.",
    server_error: "Server error. Please try again later.",
    validation_error: "Please check your input and try again.",
    auth_error: "Authentication failed. Please login again.",
    permission_denied: "You do not have permission to perform this action.",
    not_found: "The requested resource was not found.",
    something_went_wrong: "Something went wrong. Please try again.",
    too_many_requests: "Too many requests. Please slow down.",

    // Success
    saved: "Saved successfully",
    deleted: "Deleted successfully",
    created: "Created successfully",
    updated: "Updated successfully",
    sent: "Sent successfully",
    uploaded: "Uploaded successfully",

    // Time
    just_now: "Just now",
    minutes_ago: "{count} minute(s) ago",
    hours_ago: "{count} hour(s) ago",
    days_ago: "{count} day(s) ago",
    weeks_ago: "{count} week(s) ago",
    yesterday: "Yesterday",
    today: "Today",

    // Actions
    confirm: "Confirm",
    submit: "Submit",
    apply: "Apply",
    done: "Done",
    skip: "Skip",
    next: "Next",
    previous: "Previous",
    finish: "Finish",
    start: "Start",
    stop: "Stop",
    pause: "Pause",
    resume: "Resume",

    // Empty States
    no_results: "No results found",
    no_conversations: "No conversations yet",
    no_notifications: "No notifications",
    no_groups: "No groups yet",
    no_calls: "No calls yet",

    // Accessibility
    close_menu: "Close menu",
    open_menu: "Open menu",
    expand_menu: "Expand menu",
    collapse_menu: "Collapse menu",
    scroll_to_top: "Scroll to top",
    new_message: "New message",
  },

  vi: {
    // Common
    loading: "Đang tải...",
    error: "Lỗi",
    save: "Lưu",
    cancel: "Hủy",
    delete: "Xóa",
    edit: "Chỉnh sửa",
    create: "Tạo",
    search: "Tìm kiếm",
    filter: "Lọc",
    retry: "Thử lại",
    close: "Đóng",
    ok: "Đồng ý",
    yes: "Có",
    no: "Không",
    all: "Tất cả",
    none: "Không",
    select: "Chọn",
    deselect: "Bỏ chọn",
    required: "Bắt buộc",
    optional: "Tùy chọn",

    // Navigation
    dashboard: "Bảng điều khiển",
    messages: "Tin nhắn",
    groups: "Nhóm",
    calls: "Cuộc gọi",
    files: "Tệp tin",
    profile: "Hồ sơ",
    settings: "Cài đặt",
    logout: "Đăng xuất",
    login: "Đăng nhập",
    register: "Đăng ký",
    home: "Trang chủ",
    back: "Quay lại",
    forward: "Tiến",
    menu: "Menu",

    // Dashboard
    messages_count: "Tin nhắn",
    groups_count: "Nhóm",
    files_count: "Tệp tin",
    calls_count: "Cuộc gọi",
    no_data: "Không có dữ liệu",
    recent_activity: "Hoạt động gần đây",
    quick_actions: "Hành động nhanh",
    welcome_back: "Chào mừng trở lại",
    your_stats: "Thống kê của bạn",

    // Messages
    send_message: "Gửi tin nhắn",
    message_placeholder: "Nhập tin nhắn của bạn...",
    no_messages: "Chưa có tin nhắn nào",
    unread_messages: "chưa đọc",
    typing: "đang nhập...",
    is_typing: "{name} đang nhập",
    are_typing: "{names} đang nhập",
    message_sent: "Đã gửi tin nhắn",
    message_delivered: "Đã giao",
    message_read: "Đã xem",
    mark_all_read: "Đánh dấu đã đọc tất cả",
    delete_conversation: "Xóa cuộc trò chuyện",
    mute_notifications: "Tắt thông báo",
    pin_conversation: "Ghim cuộc trò chuyện",
    archive_conversation: "Lưu trữ cuộc trò chuyện",

    // Groups
    create_group: "Tạo nhóm",
    group_name: "Tên nhóm",
    group_description: "Mô tả nhóm",
    join_group: "Tham gia nhóm",
    leave_group: "Rời nhóm",
    members: "thành viên",
    member: "thành viên",
    add_members: "Thêm thành viên",
    remove_member: "Xóa thành viên",
    admin: "Quản trị viên",
    group_admin: "Quản trị nhóm",
    group_settings: "Cài đặt nhóm",
    public_group: "Nhóm công khai",
    private_group: "Nhóm riêng tư",

    // Files
    upload_file: "Tải lên tệp tin",
    download: "Tải xuống",
    file_size: "Kích thước",
    uploaded_by: "Tải lên bởi",
    no_files: "Chưa có tệp tin nào",
    drag_and_drop: "Kéo và thả tệp tin vào đây",
    browse_files: "Duyệt tệp tin",
    delete_file: "Xóa tệp tin",
    share_file: "Chia sẻ tệp tin",
    file_preview: "Xem trước tệp tin",

    // Profile
    personal_info: "Thông tin cá nhân",
    display_name: "Tên hiển thị",
    email: "Địa chỉ email",
    bio: "Tiểu sử",
    change_avatar: "Đổi ảnh đại diện",
    save_profile: "Lưu hồ sơ",
    edit_profile: "Chỉnh sửa hồ sơ",
    view_profile: "Xem hồ sơ",

    // Settings
    appearance: "Giao diện",
    theme: "Chủ đề",
    light_theme: "Sáng",
    dark_theme: "Tối",
    system_theme: "Hệ thống",
    language: "Ngôn ngữ",
    notifications: "Thông báo",
    push_notifications: "Thông báo đẩy",
    quiet_hours: "Giờ yên tĩnh",
    privacy: "Riêng tư",
    show_online: "Hiển thị trạng thái online",
    read_receipts: "Xác nhận đã đọc",
    save_settings: "Lưu cài đặt",
    reset_settings: "Khôi phục mặc định",

    // Auth
    username: "Tên đăng nhập",
    password: "Mật khẩu",
    confirm_password: "Xác nhận mật khẩu",
    forgot_password: "Quên mật khẩu?",
    remember_me: "Ghi nhớ đăng nhập",
    dont_have_account: "Chưa có tài khoản?",
    already_have_account: "Đã có tài khoản?",
    sign_up: "Đăng ký",
    sign_in: "Đăng nhập",
    sign_out: "Đăng xuất",

    // Errors
    network_error: "Lỗi mạng. Vui lòng kiểm tra kết nối.",
    server_error: "Lỗi máy chủ. Vui lòng thử lại sau.",
    validation_error: "Vui lòng kiểm tra thông tin nhập và thử lại.",
    auth_error: "Xác thực thất bại. Vui lòng đăng nhập lại.",
    permission_denied: "Bạn không có quyền thực hiện hành động này.",
    not_found: "Tài nguyên yêu cầu không tồn tại.",
    something_went_wrong: "Đã xảy ra lỗi. Vui lòng thử lại.",
    too_many_requests: "Quá nhiều yêu cầu. Vui lòng chậm lại.",

    // Success
    saved: "Đã lưu thành công",
    deleted: "Đã xóa thành công",
    created: "Đã tạo thành công",
    updated: "Đã cập nhật thành công",
    sent: "Đã gửi thành công",
    uploaded: "Đã tải lên thành công",

    // Time
    just_now: "Vừa xong",
    minutes_ago: "{count} phút trước",
    hours_ago: "{count} giờ trước",
    days_ago: "{count} ngày trước",
    weeks_ago: "{count} tuần trước",
    yesterday: "Hôm qua",
    today: "Hôm nay",

    // Actions
    confirm: "Xác nhận",
    submit: "Gửi",
    apply: "Áp dụng",
    done: "Hoàn thành",
    skip: "Bỏ qua",
    next: "Tiếp theo",
    previous: "Trước",
    finish: "Kết thúc",
    start: "Bắt đầu",
    stop: "Dừng",
    pause: "Tạm dừng",
    resume: "Tiếp tục",

    // Empty States
    no_results: "Không tìm thấy kết quả",
    no_conversations: "Chưa có cuộc trò chuyện nào",
    no_notifications: "Không có thông báo",
    no_groups: "Chưa có nhóm nào",
    no_calls: "Chưa có cuộc gọi nào",

    // Accessibility
    close_menu: "Đóng menu",
    open_menu: "Mở menu",
    expand_menu: "Mở rộng menu",
    collapse_menu: "Thu gọn menu",
    scroll_to_top: "Cuộn lên đầu",
    new_message: "Tin nhắn mới",

    // Government - Phường/Xã
    government: "Quản lý Nhà nước",
    citizens: "Công dân",
    officers: "Cán bộ",
    tthc: "Thủ tục hành chính",
    documents: "Tài liệu",
    statistics: "Thống kê",
    ward: "Phường/Xã",
    district: "Quận/Huyện",
    province: "Tỉnh/Thành phố",
    population: "Dân số",
    household: "Hộ gia đình",
    birth: "Đăng ký khai sinh",
    death: "Đăng ký khai tử",
    marriage: "Đăng ký kết hôn",
    relocation: "Di chuyển",
    identity_card: "Căn cước công dân",
    permanent_residence: "Đăng ký thường trú",
    temporary_residence: "Đăng ký tạm trú",

    // Smart City
    smart_city: "Thành phố thông minh",
    iot_devices: "Thiết bị IoT",
    environment: "Giám sát môi trường",
    gis_map: "Bản đồ GIS",
    air_quality: "Chất lượng không khí",
    temperature: "Nhiệt độ",
    humidity: "Độ ẩm",
    noise_level: "Mức độ tiếng ồn",
    traffic: "Giao thông",
    energy: "Năng lượng",
    waste: "Xử lý rác thải",
    security: "Giám sát an ninh",

    // Mobile App
    mobile_app: "Ứng dụng di động",
    offline_mode: "Chế độ ngoại tuyến",
    sync_data: "Đồng bộ dữ liệu",
    last_sync: "Đồng bộ lần cuối",
    sync_pending: "Chờ đồng bộ",
    connection_lost: "Mất kết nối",
  },

  es: {
    // Common
    loading: "Cargando...",
    error: "Error",
    save: "Guardar",
    cancel: "Cancelar",
    delete: "Eliminar",
    edit: "Editar",
    create: "Crear",
    search: "Buscar",
    filter: "Filtrar",
    retry: "Reintentar",
    close: "Cerrar",
    ok: "Aceptar",
    yes: "Sí",
    no: "No",
    all: "Todo",
    none: "Ninguno",
    select: "Seleccionar",
    deselect: "Deseleccionar",
    required: "Requerido",
    optional: "Opcional",

    // Navigation
    dashboard: "Panel de control",
    messages: "Mensajes",
    groups: "Grupos",
    calls: "Llamadas",
    files: "Archivos",
    profile: "Perfil",
    settings: "Configuración",
    logout: "Cerrar sesión",
    login: "Iniciar sesión",
    register: "Registrarse",
    home: "Inicio",
    back: "Atrás",
    forward: "Adelante",
    menu: "Menú",

    // Dashboard
    messages_count: "Mensajes",
    groups_count: "Grupos",
    files_count: "Archivos",
    calls_count: "Llamadas",
    no_data: "No hay datos disponibles",
    recent_activity: "Actividad reciente",
    quick_actions: "Acciones rápidas",
    welcome_back: "Bienvenido de nuevo",
    your_stats: "Tus estadísticas",

    // Messages
    send_message: "Enviar mensaje",
    message_placeholder: "Escribe tu mensaje...",
    no_messages: "Aún no hay mensajes",
    unread_messages: "sin leer",
    typing: "escribiendo...",
    is_typing: "{name} está escribiendo",
    are_typing: "{names} están escribiendo",
    message_sent: "Mensaje enviado",
    message_delivered: "Entregado",
    message_read: "Leído",

    // Groups
    create_group: "Crear grupo",
    group_name: "Nombre del grupo",
    group_description: "Descripción del grupo",
    join_group: "Unirse al grupo",
    leave_group: "Abandonar grupo",
    members: "miembros",
    member: "miembro",
    add_members: "Añadir miembros",
    remove_member: "Eliminar miembro",
    admin: "Admin",
    group_admin: "Admin del grupo",
    group_settings: "Configuración del grupo",
    public_group: "Grupo público",
    private_group: "Grupo privado",

    // Files
    upload_file: "Subir archivo",
    download: "Descargar",
    file_size: "Tamaño",
    uploaded_by: "Subido por",
    no_files: "Aún no hay archivos",
    drag_and_drop: "Arrastra y suelta archivos aquí",
    browse_files: "Explorar archivos",

    // Profile
    personal_info: "Información personal",
    display_name: "Nombre para mostrar",
    email: "Correo electrónico",
    bio: "Biografía",
    change_avatar: "Cambiar avatar",
    save_profile: "Guardar perfil",
    edit_profile: "Editar perfil",
    view_profile: "Ver perfil",

    // Settings
    appearance: "Apariencia",
    theme: "Tema",
    light_theme: "Claro",
    dark_theme: "Oscuro",
    system_theme: "Sistema",
    language: "Idioma",
    notifications: "Notificaciones",
    push_notifications: "Notificaciones push",
    quiet_hours: "Horas de silencio",
    privacy: "Privacidad",
    show_online: "Mostrar estado en línea",
    read_receipts: "Confirmaciones de lectura",
    save_settings: "Guardar configuración",
    reset_settings: "Restablecer predeterminados",

    // Auth
    username: "Nombre de usuario",
    password: "Contraseña",
    confirm_password: "Confirmar contraseña",
    forgot_password: "¿Olvidaste tu contraseña?",
    remember_me: "Recordarme",
    dont_have_account: "¿No tienes cuenta?",
    already_have_account: "¿Ya tienes cuenta?",
    sign_up: "Registrarse",
    sign_in: "Iniciar sesión",
    sign_out: "Cerrar sesión",

    // Errors
    network_error: "Error de red. Por favor verifica tu conexión.",
    server_error: "Error del servidor. Por favor intenta de nuevo más tarde.",
    validation_error: "Por favor verifica tu entrada e intenta de nuevo.",
    auth_error: "Autenticación fallida. Por favor inicia sesión de nuevo.",
    permission_denied: "No tienes permiso para realizar esta acción.",
    not_found: "El recurso solicitado no fue encontrado.",
    something_went_wrong: "Algo salió mal. Por favor intenta de nuevo.",
    too_many_requests: "Demasiadas solicitudes. Por favor slow down.",

    // Success
    saved: "Guardado exitosamente",
    deleted: "Eliminado exitosamente",
    created: "Creado exitosamente",
    updated: "Actualizado exitosamente",
    sent: "Enviado exitosamente",
    uploaded: "Subido exitosamente",

    // Time
    just_now: "Ahora mismo",
    minutes_ago: "hace {count} minuto(s)",
    hours_ago: "hace {count} hora(s)",
    days_ago: "hace {count} día(s)",
    weeks_ago: "hace {count} semana(s)",
    yesterday: "Ayer",
    today: "Hoy",

    // Actions
    confirm: "Confirmar",
    submit: "Enviar",
    apply: "Aplicar",
    done: "Hecho",
    skip: "Omitir",
    next: "Siguiente",
    previous: "Anterior",
    finish: "Finalizar",
    start: "Iniciar",
    stop: "Detener",
    pause: "Pausar",
    resume: "Reanudar",

    // Empty States
    no_results: "No se encontraron resultados",
    no_conversations: "Aún no hay conversaciones",
    no_notifications: "Sin notificaciones",
    no_groups: "Aún no hay grupos",
    no_calls: "Aún no hay llamadas",

    // Accessibility
    close_menu: "Cerrar menú",
    open_menu: "Abrir menú",
    expand_menu: "Expandir menú",
    collapse_menu: "Contraer menú",
    scroll_to_top: "Desplazarse arriba",
    new_message: "Nuevo mensaje",
  },

  zh: {
    loading: "加载中...",
    error: "错误",
    save: "保存",
    cancel: "取消",
    delete: "删除",
    edit: "编辑",
    create: "创建",
    search: "搜索",
    filter: "筛选",
    retry: "重试",
    close: "关闭",
    ok: "确定",
    yes: "是",
    no: "否",
    all: "全部",
    none: "无",
    select: "选择",
    deselect: "取消选择",
    required: "必填",
    optional: "可选",
    dashboard: "仪表板",
    messages: "消息",
    groups: "群组",
    calls: "通话",
    files: "文件",
    profile: "个人资料",
    settings: "设置",
    logout: "退出登录",
    login: "登录",
    register: "注册",
    home: "首页",
    back: "返回",
    forward: "前进",
    menu: "菜单",
    messages_count: "消息",
    groups_count: "群组",
    files_count: "文件",
    calls_count: "通话",
    no_data: "暂无数据",
    recent_activity: "最近活动",
    quick_actions: "快捷操作",
    welcome_back: "欢迎回来",
    your_stats: "您的统计",
    send_message: "发送消息",
    message_placeholder: "输入您的消息...",
    no_messages: "暂无消息",
    unread_messages: "未读",
    typing: "正在输入...",
    is_typing: "{name} 正在输入",
    are_typing: "{names} 正在输入",
    message_sent: "消息已发送",
    message_delivered: "已送达",
    message_read: "已读",
    mark_all_read: "全部标为已读",
    delete_conversation: "删除对话",
    mute_notifications: "静音通知",
    pin_conversation: "置顶对话",
    archive_conversation: "归档对话",
    create_group: "创建群组",
    group_name: "群组名称",
    group_description: "群组描述",
    join_group: "加入群组",
    leave_group: "退出群组",
    members: "成员",
    member: "成员",
    add_members: "添加成员",
    remove_member: "移除成员",
    admin: "管理员",
    group_admin: "群管理员",
    group_settings: "群设置",
    public_group: "公开群",
    private_group: "私密群",
    upload_file: "上传文件",
    download: "下载",
    file_size: "大小",
    uploaded_by: "上传者",
    no_files: "暂无文件",
    drag_and_drop: "拖放文件到这里",
    browse_files: "浏览文件",
    delete_file: "删除文件",
    share_file: "分享文件",
    file_preview: "文件预览",
    personal_info: "个人信息",
    display_name: "显示名称",
    email: "电子邮箱",
    bio: "简介",
    change_avatar: "更换头像",
    save_profile: "保存资料",
    edit_profile: "编辑资料",
    view_profile: "查看资料",
    appearance: "外观",
    theme: "主题",
    light_theme: "浅色",
    dark_theme: "深色",
    system_theme: "跟随系统",
    language: "语言",
    notifications: "通知",
    push_notifications: "推送通知",
    quiet_hours: "勿扰时段",
    privacy: "隐私",
    show_online: "显示在线状态",
    read_receipts: "已读回执",
    save_settings: "保存设置",
    reset_settings: "恢复默认",
    username: "用户名",
    password: "密码",
    confirm_password: "确认密码",
    forgot_password: "忘记密码？",
    remember_me: "记住我",
    dont_have_account: "没有账号？",
    already_have_account: "已有账号？",
    sign_up: "注册",
    sign_in: "登录",
    sign_out: "退出",
    network_error: "网络错误，请检查您的连接。",
    server_error: "服务器错误，请稍后再试。",
    validation_error: "请检查输入并重试。",
    auth_error: "认证失败，请重新登录。",
    permission_denied: "您没有执行此操作的权限。",
    not_found: "请求的资源未找到。",
    something_went_wrong: "出错了，请重试。",
    too_many_requests: "请求过多，请稍后再试。",
    saved: "保存成功",
    deleted: "删除成功",
    created: "创建成功",
    updated: "更新成功",
    sent: "发送成功",
    uploaded: "上传成功",
    just_now: "刚刚",
    minutes_ago: "{count}分钟前",
    hours_ago: "{count}小时前",
    days_ago: "{count}天前",
    weeks_ago: "{count}周前",
    yesterday: "昨天",
    today: "今天",
    confirm: "确认",
    submit: "提交",
    apply: "应用",
    done: "完成",
    skip: "跳过",
    next: "下一步",
    previous: "上一步",
    finish: "结束",
    start: "开始",
    stop: "停止",
    pause: "暂停",
    resume: "继续",
    no_results: "未找到结果",
    no_conversations: "暂无对话",
    no_notifications: "暂无通知",
    no_groups: "暂无群组",
    no_calls: "暂无通话",
    close_menu: "关闭菜单",
    open_menu: "打开菜单",
    expand_menu: "展开菜单",
    collapse_menu: "折叠菜单",
    scroll_to_top: "回到顶部",
    new_message: "新消息",
    government: "政府管理",
    citizens: "公民",
    officers: "干部",
    tthc: "行政程序",
    documents: "文档",
    statistics: "统计",
    ward: "坊/乡",
    district: "县/郡",
    province: "省/市",
    population: "人口",
    household: "户",
    birth: "出生登记",
    death: "死亡登记",
    marriage: "婚姻登记",
    relocation: "迁入/迁出",
    identity_card: "身份证",
    permanent_residence: "常住登记",
    temporary_residence: "临时登记",
    smart_city: "智慧城市",
    iot_devices: "物联网设备",
    environment: "环境监测",
    gis_map: "地理信息系统",
    air_quality: "空气质量",
    temperature: "温度",
    humidity: "湿度",
    noise_level: "噪音水平",
    traffic: "交通",
    energy: "能源",
    waste: "垃圾处理",
    security: "安全监控",
    mobile_app: "移动应用",
    offline_mode: "离线模式",
    sync_data: "同步数据",
    last_sync: "上次同步",
    sync_pending: "待同步",
    connection_lost: "连接已断开",
  },
};

// ============================================================================
// STORES
// ============================================================================

const defaultLocale: Locale = "en";

// Current locale
export const locale = writable<Locale>(defaultLocale);

// Whether translations are loading
export const isLoading = writable<boolean>(false);

// Current translations loaded
const currentTranslations = writable<TranslationMap>(
  DEFAULT_TRANSLATIONS[defaultLocale],
);

// ============================================================================
// FUNCTIONS
// ============================================================================

// Get locale from browser navigator
function getLocaleFromNavigator(): Locale {
  if (typeof navigator !== "undefined" && navigator.language) {
    const navLang = navigator.language.split("-")[0].toLowerCase();
    const supportedLocale = Object.keys(SUPPORTED_LOCALES).find(
      (l) => l === navLang,
    );
    if (supportedLocale) {
      return supportedLocale as Locale;
    }
  }
  return defaultLocale;
}

// Initialize i18n
export async function initI18n(initialLocale?: Locale): Promise<void> {
  isLoading.set(true);

  const selectedLocale = initialLocale ?? getLocaleFromNavigator();
  locale.set(selectedLocale);

  await loadTranslations(selectedLocale);

  isLoading.set(false);
}

// Load translations for a locale
async function loadTranslations(lang: Locale): Promise<void> {
  try {
    // Try to load from server first
    const response = await fetch(`/locales/${lang}.json`);
    if (response.ok) {
      const serverTranslations = await response.json();
      currentTranslations.set(serverTranslations);
      return;
    }
  } catch (error) {
    console.warn(`Failed to load translations for ${lang}:`, error);
  }

  // Fall back to embedded translations
  if (DEFAULT_TRANSLATIONS[lang]) {
    currentTranslations.set(DEFAULT_TRANSLATIONS[lang]);
  } else {
    currentTranslations.set(DEFAULT_TRANSLATIONS[defaultLocale]);
  }
}

// Set locale and load translations
export async function setLocale(newLocale: Locale): Promise<void> {
  if (!SUPPORTED_LOCALES[newLocale]) {
    console.warn(`Unsupported locale: ${newLocale}`);
    return;
  }

  locale.set(newLocale);
  await loadTranslations(newLocale);

  // Save to localStorage
  if (browser && typeof localStorage !== "undefined") {
    localStorage.setItem("preferred_locale", newLocale);
  }
}

// Get translation for a key
export function _(key: string, params?: TranslationValue): string {
  const translations = get(currentTranslations);
  let translation = translations[key] as string | undefined;

  if (!translation) {
    // Try fallback to English
    const englishTranslation = DEFAULT_TRANSLATIONS.en[key] as
      | string
      | undefined;
    translation = englishTranslation ?? key;
  }

  // Replace parameters
  if (params) {
    translation = translation.replace(/\{(\w+)\}/g, (match, paramName) => {
      const value = params[paramName];
      return value !== undefined ? String(value) : match;
    });
  }

  return translation;
}

// Pluralize translation
export function n(
  key: string,
  count: number,
  params?: TranslationValue,
): string {
  const pluralKey = count === 1 ? `${key}_one` : `${key}_other`;
  let translation = _(pluralKey, { count, ...params });

  // Fallback to regular key if plural not found
  if (translation === pluralKey || !translation) {
    translation = _(key, { count, ...params });
  }

  return translation;
}

// Number formatting
export function formatNumber(
  value: number,
  options?: Intl.NumberFormatOptions,
): string {
  const lang = get(locale);
  if (!Number.isFinite(value)) {
    return '';
  }
  try {
    return new Intl.NumberFormat(lang, options).format(value);
  } catch (e) {
    console.warn('formatNumber error:', e);
    return String(value);
  }
}

// Currency formatting
export function formatCurrency(
  value: number,
  currency?: string,
  options?: Intl.NumberFormatOptions,
): string {
  const lang = get(locale);
  if (!Number.isFinite(value)) {
    return '';
  }
  try {
    return new Intl.NumberFormat(lang, {
      style: 'currency',
      currency: currency || 'USD',
      ...options,
    }).format(value);
  } catch (e) {
    console.warn('formatCurrency error:', e);
    return String(value);
  }
}

// Date formatting
export function formatDate(
  value: string | Date,
  options?: Intl.DateTimeFormatOptions,
): string {
  const lang = get(locale);
  const date = typeof value === "string" ? new Date(value) : value;
  if (!(date instanceof Date) || isNaN(date.getTime())) {
    return '';
  }
  try {
    return new Intl.DateTimeFormat(lang, options).format(date);
  } catch (e) {
    console.warn('formatDate error:', e);
    return String(value);
  }
}

// Relative time formatting
export function formatRelativeTime(value: string | Date): string {
  const date = typeof value === "string" ? new Date(value) : value;
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffSecs = Math.floor(diffMs / 1000);
  const diffMins = Math.floor(diffSecs / 60);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);
  const diffWeeks = Math.floor(diffDays / 7);

  const lang = get(locale);
  const t = (key: string, count: number) => _(key, { count });

  if (diffSecs < 60) {
    return _("just_now");
  } else if (diffMins < 60) {
    return t("minutes_ago", diffMins);
  } else if (diffHours < 24) {
    return t("hours_ago", diffHours);
  } else if (diffDays < 7) {
    return t("days_ago", diffDays);
  } else if (diffWeeks < 4) {
    return t("weeks_ago", diffWeeks);
  } else {
    return formatDate(date);
  }
}

// Get direction for current locale
export const isRTL = derived(locale, ($locale) => {
  return RTL_LOCALES.includes($locale);
});

// Get current locale config
export const currentLocaleConfig = derived(locale, ($locale) => {
  return SUPPORTED_LOCALES[$locale] ?? SUPPORTED_LOCALES[defaultLocale];
});

// ============================================================================
// EXPORTS
// ============================================================================

export { DEFAULT_TRANSLATIONS as translations };

export default {
  init: initI18n,
  setLocale,
  _: _,
  n: n,
  formatNumber,
  formatDate,
  formatRelativeTime,
  locale,
  isRTL,
  currentLocaleConfig,
};
