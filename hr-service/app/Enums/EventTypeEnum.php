<?php

namespace App\Enums;

enum EventTypeEnum: string
{
    case EMPLOYEE_CREATED = 'EmployeeCreated';
    case EMPLOYEE_UPDATED = 'EmployeeUpdated';
    case EMPLOYEE_DELETED = 'EmployeeDeleted';
}
