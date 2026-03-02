<?php

namespace App\Enums;

enum EventTypeEnum: string
{
    case EMPLOYEE_CREATED = 'employee.created';
    case EMPLOYEE_UPDATED = 'employee.updated';
    case EMPLOYEE_DELETED = 'employee.deleted';
}
