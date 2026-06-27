<?php

namespace App\Enums;

enum UserType: string
{
    case SUPER_ADMIN = 'super_admin';
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case SUPERVISOR = 'supervisor';
    case EMPLOYEE = 'employee';
}