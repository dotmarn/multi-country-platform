<?php

namespace App\Enums;

enum EventTypesEnum: string
{
    case EMPLOYEE_CREATED = 'employee.created';
    case EMPLOYEE_UPDATED = 'employee.updated';
    case EMPLOYEE_DELETED = 'employee.deleted';
    case CHECKLIST_UPDATED = 'checklist.updated';
}
