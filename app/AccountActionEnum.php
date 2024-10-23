<?php

namespace App;

enum AccountActionEnum
{
    case LOCK;
    case RESTRICT;
    case LOGIN;
    case LOGOUT;
    case SENT_RESET_LINK;
    case RESET_PASSWORD;
}
