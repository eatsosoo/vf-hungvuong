<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Sales = 'sales';
    case Editor = 'editor';
}
