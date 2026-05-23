<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'Admin';
    case Student = 'Student';
    case Supervisor = 'Supervisor';
    case CommitteeMember = 'CommitteeMember';

    public static function default(): self
    {
        return self::Student;
    }
}
