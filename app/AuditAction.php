<?php

namespace App;

enum AuditAction: string
{
    case RETRIEVE = 'retrieved';
    case CREATE = 'created';
    case UPDATE = 'updated';
    case LOCK = 'locked';
    case RESTRICT = 'restricted';
    case UNRESTRICT = 'unrestricted';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case SENT_RESET_LINK = 'sent reset link';
    case RESET_PASSWORD = 'reset password';
    case DOWNLOAD = 'downloaded';
}
