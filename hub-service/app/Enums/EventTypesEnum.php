<?php

namespace App\Enums;

enum EventTypesEnum: string
{
    case EMPLOYEE_CREATED = 'EmployeeCreated';
    case EMPLOYEE_UPDATED = 'EmployeeUpdated';
    case EMPLOYEE_DELETED = 'EmployeeDeleted';
    case CHECKLIST_UPDATED = 'checklist.updated';
    case EMPLOYEE_LIST_UPDATED = 'employee.list.updated';
    case EMPLOYEE_DATA_CHANGED = 'employee.data.changed';

}
