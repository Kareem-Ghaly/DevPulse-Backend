<?php

namespace Database\Seeders;

use App\Models\CommitteeMemberProfile;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $studentRole = 'Student';
        $supervisorRole = 'Supervisor';
        $committeeRole = 'CommitteeMember';

        Role::firstOrCreate([
            'name' => $studentRole,
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => $supervisorRole,
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => $committeeRole,
            'guard_name' => 'web',
        ]);

        DB::transaction(function () use (
            $studentRole,
            $supervisorRole,
            $committeeRole
        ): void {
            $this->seedStudents($studentRole);
            $this->seedSupervisors($supervisorRole);
            $this->seedCommitteeMember($committeeRole);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedStudents(string $role): void
    {
        $students = [
            [
                'name' => 'Kareem Hisham Ghaly',
                'username' => 'kareem_ghaly',
                'email' => 'kareemghaly@gmail.com',
                'full_name' => 'Kareem Hisham Ghaly',
                'university_id' => '2024011',
                'department' => 'Software Engineering',
                'academic_year' => 'Fifth Year',
                'skills' => [
                    'LARAVEL',
                    'PHP',
                    'MYSQL',
                    'REST API',
                    'VUE',
                    'NUXT',
                    'TYPESCRIPT',
                    'FIREBASE',
                    'LARAVEL REVERB',
                    'WEBSOCKETS',
                    'DOCKER',
                    'GIT',
                    'GITHUB',
                    'LINUX',
                    'NGINX',
                ],
                'bio' => 'Full-stack developer focused on Laravel, RESTful APIs, database design and layered software architecture.',
            ],
            [
                'name' => 'Mohamad Hossam Rabata',
                'username' => 'mohamad_rabata',
                'email' => 'mohamad.rabata.devpulse@gmail.com',
                'full_name' => 'Mohamad Hossam Rabata',
                'university_id' => '2024012',
                'department' => 'Software Engineering',
                'academic_year' => 'Fifth Year',
                'skills' => ['LARAVEL', 'PHP', 'MYSQL', 'REST API', 'DOCKER', 'GIT', 'GITHUB'],
                'bio' => 'Backend developer focused on Laravel, database design, authentication and RESTful API development.',
            ],
            [
                'name' => 'Omar Mahmoud',
                'username' => 'omar_mahmoud',
                'email' => 'omar.mahmoud.devpulse@gmail.com',
                'full_name' => 'Omar Yasser Mahmoud',
                'university_id' => '2024003',
                'department' => 'Software Engineering',
                'academic_year' => 'Fifth Year',
                'skills' => ['LARAVEL', 'VUE', 'JAVASCRIPT', 'TYPESCRIPT', 'MYSQL', 'DOCKER', 'GIT'],
                'bio' => 'Full-stack developer interested in modern web application development.',
            ],
            [
                'name' => 'Lina Khalil',
                'username' => 'lina_khalil',
                'email' => 'lina.khalil.devpulse@gmail.com',
                'full_name' => 'Lina Samer Khalil',
                'university_id' => '2024004',
                'department' => 'Artificial Intelligence',
                'academic_year' => 'Fourth Year',
                'skills' => ['PYTHON', 'TENSORFLOW', 'PYTORCH', 'HUGGING FACE', 'LANGCHAIN', 'OPENAI API'],
                'bio' => 'Interested in machine learning, data processing and intelligent systems.',
            ],
            [
                'name' => 'Youssef Hamdan',
                'username' => 'youssef_hamdan',
                'email' => 'youssef.hamdan.devpulse@gmail.com',
                'full_name' => 'Youssef Mazen Hamdan',
                'university_id' => '2024005',
                'department' => 'Software Engineering',
                'academic_year' => 'Fifth Year',
                'skills' => ['FLUTTER', 'KOTLIN', 'FIREBASE', 'REST API', 'GIT'],
                'bio' => 'Mobile application developer specializing in Flutter and Firebase.',
            ],
            [
                'name' => 'Nour Ibrahim',
                'username' => 'nour_ibrahim',
                'email' => 'nour.ibrahim.devpulse@gmail.com',
                'full_name' => 'Nour Ahmad Ibrahim',
                'university_id' => '2024006',
                'department' => 'Information Systems',
                'academic_year' => 'Fourth Year',
                'skills' => ['VUE', 'NUXT', 'JAVASCRIPT', 'TYPESCRIPT', 'HTML', 'CSS', 'TAILWIND'],
                'bio' => 'UI/UX designer interested in creating accessible and user-friendly products.',
            ],
            [
                'name' => 'Mohammad Saleh',
                'username' => 'mohammad_saleh',
                'email' => 'mohammad.saleh.devpulse@gmail.com',
                'full_name' => 'Mohammad Hossam Saleh',
                'university_id' => '2024007',
                'department' => 'Software Engineering',
                'academic_year' => 'Fifth Year',
                'skills' => ['LARAVEL', 'PHP', 'MYSQL', 'REDIS', 'DOCKER', 'REST API'],
                'bio' => 'Backend developer interested in database design and application performance.',
            ],
            [
                'name' => 'Christine Habib',
                'username' => 'christine_habib',
                'email' => 'christine.habib.devpulse@gmail.com',
                'full_name' => 'Christine Maan Habib',
                'university_id' => '2024008',
                'department' => 'Software Engineering',
                'academic_year' => 'Fifth Year',
                'skills' => ['VUE', 'NUXT', 'TYPESCRIPT', 'JAVASCRIPT', 'HTML', 'CSS', 'TAILWIND'],
                'bio' => 'Frontend developer focused on modern interfaces and reusable components.',
            ],
            [
                'name' => 'Rami Abbas',
                'username' => 'rami_abbas',
                'email' => 'rami.abbas.devpulse@gmail.com',
                'full_name' => 'Rami Fadi Abbas',
                'university_id' => '2024009',
                'department' => 'Networks and Security',
                'academic_year' => 'Fourth Year',
                'skills' => ['CISCO', 'WIRESHARK', 'TCP/IP', 'DNS', 'HTTP/HTTPS', 'LINUX', 'DOCKER'],
                'bio' => 'Interested in networks, application security and cloud infrastructure.',
            ],
            [
                'name' => 'Maya Darwish',
                'username' => 'maya_darwish',
                'email' => 'maya.darwish.devpulse@gmail.com',
                'full_name' => 'Maya Wael Darwish',
                'university_id' => '2024010',
                'department' => 'Information Systems',
                'academic_year' => 'Fifth Year',
                'skills' => ['SQL', 'MYSQL', 'POSTGRESQL', 'REST API', 'N8N', 'GIT'],
                'bio' => 'Interested in systems analysis, requirements engineering and project management.',
            ],
        ];

        foreach ($students as $studentData) {
            $user = User::updateOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'username' => $studentData['username'],
                    'password' => 'Password@123',
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'profile_completed' => true,
                ]
            );

            $user->syncRoles([$role]);

            StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $studentData['full_name'],
                    'university_id' => $studentData['university_id'],
                    'department' => $studentData['department'],
                    'academic_year' => $studentData['academic_year'],
                    'skills' => $studentData['skills'],
                    'bio' => $studentData['bio'],
                ]
            );
        }
    }

    private function seedSupervisors(string $role): void
    {
        $supervisors = [
            [
                'name' => 'Saeed Alnehlawi',
                'username' => 'saeed_alnehlawi',
                'email' => 'saeed.alnehlawi.devpulse@gmail.com',
                'full_name' => 'Saeed Alnehlawi',
                'academic_title' => 'Associate Professor',
                'department' => 'Software Engineering',
                'specialization' => 'Web Application Engineering',
                'research_interests' => [
                    'LARAVEL',
                    'PHP',
                    'MYSQL',
                    'REST API',
                    'DOCKER',
                    'GIT',
                ],
                'office_hours' => 'Sunday and Tuesday, 10:00 AM - 12:00 PM',
                'bio' => 'Specialized in web engineering, software architecture and backend development.',
            ],
            [
                'name' => 'Khaled Ismael',
                'username' => 'khaled_ismael',
                'email' => 'khaled.ismael.devpulse@gmail.com',
                'full_name' => 'Khaled Ismael',
                'academic_title' => 'Assistant Professor',
                'department' => 'Software Engineering',
                'specialization' => 'Frontend Engineering',
                'research_interests' => [
                    'VUE',
                    'NUXT',
                    'TYPESCRIPT',
                    'JAVASCRIPT',
                    'HTML',
                    'CSS',
                    'TAILWIND',
                ],
                'office_hours' => 'Monday and Wednesday, 11:00 AM - 1:00 PM',
                'bio' => 'Interested in frontend engineering, interaction design and usability.',
            ],
            [
                'name' => 'Adnan Qattaya',
                'username' => 'adnan_qattaya',
                'email' => 'adnan.qattaya.devpulse@gmail.com',
                'full_name' => 'Adnan Qattaya',
                'academic_title' => 'Professor',
                'department' => 'Software Engineering',
                'specialization' => 'Software Quality Assurance',
                'research_interests' => [
                    'LARAVEL',
                    'PHP',
                    'REST API',
                    'DOCKER',
                    'GIT',
                    'GITHUB',
                ],
                'office_hours' => 'Sunday, 9:00 AM - 12:00 PM',
                'bio' => 'Specialized in software testing, quality assurance and continuous integration.',
            ],
            [
                'name' => 'Nour Alhakem',
                'username' => 'nour_alhakem',
                'email' => 'nour.alhakem.devpulse@gmail.com',
                'full_name' => 'Nour Alhakem',
                'academic_title' => 'Associate Professor',
                'department' => 'Software Engineering',
                'specialization' => 'Software Project Management',
                'research_interests' => [
                    'REST API',
                    'N8N',
                    'SQL',
                    'MYSQL',
                    'GIT',
                    'GITHUB',
                ],
                'office_hours' => 'Tuesday and Thursday, 10:00 AM - 12:00 PM',
                'bio' => 'Specialized in software project management, Agile practices and requirements engineering.',
            ],
            [
                'name' => 'Dr. Fadi Othman',
                'username' => 'dr_fadi_othman',
                'email' => 'fadi.othman.devpulse@gmail.com',
                'full_name' => 'Dr. Fadi Othman',
                'academic_title' => 'Assistant Professor',
                'department' => 'Networks and Security',
                'specialization' => 'Cybersecurity',
                'research_interests' => [
                    'CISCO',
                    'WIRESHARK',
                    'TCP/IP',
                    'DNS',
                    'HTTP/HTTPS',
                    'LINUX',
                ],
                'office_hours' => 'Monday, 10:00 AM - 1:00 PM',
                'bio' => 'Interested in cybersecurity, secure applications and cloud infrastructure.',
            ],
            [
                'name' => 'Dr. Reem Al Khatib',
                'username' => 'dr_reem_alkhatib',
                'email' => 'reem.alkhatib.devpulse@gmail.com',
                'full_name' => 'Dr. Reem Al Khatib',
                'academic_title' => 'Assistant Professor',
                'department' => 'Software Engineering',
                'specialization' => 'Mobile Application Development',
                'research_interests' => [
                    'FLUTTER',
                    'KOTLIN',
                    'SWIFT',
                    'REACT NATIVE',
                    'FIREBASE',
                    'REST API',
                ],
                'office_hours' => 'Wednesday, 9:00 AM - 12:00 PM',
                'bio' => 'Specialized in cross-platform mobile applications and cloud integration.',
            ],
            [
                'name' => 'Dr. Tarek Ismail',
                'username' => 'dr_tarek_ismail',
                'email' => 'tarek.ismail.devpulse@gmail.com',
                'full_name' => 'Dr. Tarek Ismail',
                'academic_title' => 'Associate Professor',
                'department' => 'Software Engineering',
                'specialization' => 'Database Systems',
                'research_interests' => [
                    'MYSQL',
                    'POSTGRESQL',
                    'SQL',
                    'REDIS',
                    'LARAVEL',
                ],
                'office_hours' => 'Sunday and Wednesday, 1:00 PM - 3:00 PM',
                'bio' => 'Specialized in database systems, optimization and backend engineering.',
            ],
            [
                'name' => 'Dr. Dima Hassan',
                'username' => 'dr_dima_hassan',
                'email' => 'dima.hassan.devpulse@gmail.com',
                'full_name' => 'Dr. Dima Hassan',
                'academic_title' => 'Assistant Professor',
                'department' => 'Artificial Intelligence',
                'specialization' => 'Machine Learning and Deep Learning',
                'research_interests' => [
                    'PYTHON',
                    'TENSORFLOW',
                    'PYTORCH',
                    'HUGGING FACE',
                ],
                'office_hours' => 'Monday and Thursday, 12:00 PM - 2:00 PM',
                'bio' => 'Interested in machine learning, deep learning and intelligent software systems.',
            ],
            [
                'name' => 'Dr. Wael Ibrahim',
                'username' => 'dr_wael_ibrahim',
                'email' => 'wael.ibrahim.devpulse@gmail.com',
                'full_name' => 'Dr. Wael Ibrahim',
                'academic_title' => 'Professor',
                'department' => 'Artificial Intelligence',
                'specialization' => 'Natural Language Processing',
                'research_interests' => [
                    'PYTHON',
                    'LANGCHAIN',
                    'OPENAI API',
                    'GEMINI API',
                    'LLAMAININDEX',
                ],
                'office_hours' => 'Tuesday, 9:00 AM - 12:00 PM',
                'bio' => 'Specialized in natural language processing and intelligent application development.',
            ],
            [
                'name' => 'Dr. Nour Al Din Saleh',
                'username' => 'dr_nour_saleh',
                'email' => 'nour.saleh.devpulse@gmail.com',
                'full_name' => 'Dr. Nour Al Din Saleh',
                'academic_title' => 'Associate Professor',
                'department' => 'Networks and Security',
                'specialization' => 'Cloud Computing and DevOps',
                'research_interests' => [
                    'LINUX',
                    'NGINX',
                    'DOCKER',
                    'KUBERNETES',
                    'AWS',
                    'TCP/IP',
                    'DNS',
                ],
                'office_hours' => 'Thursday, 10:00 AM - 1:00 PM',
                'bio' => 'Interested in cloud computing, DevOps practices and deployment automation.',
            ],
        ];

        foreach ($supervisors as $supervisorData) {
            $user = User::updateOrCreate(
                ['email' => $supervisorData['email']],
                [
                    'name' => $supervisorData['name'],
                    'username' => $supervisorData['username'],
                    'password' => 'Password@123',
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'profile_completed' => true,
                ]
            );

            $user->syncRoles([$role]);

            SupervisorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $supervisorData['full_name'],
                    'academic_title' => $supervisorData['academic_title'],
                    'department' => $supervisorData['department'],
                    'specialization' => $supervisorData['specialization'],
                    'research_interests' => $supervisorData['research_interests'],
                    'office_hours' => $supervisorData['office_hours'],
                    'bio' => $supervisorData['bio'],
                ]
            );
        }
    }

    private function seedCommitteeMember(string $role): void
    {
        $user = User::updateOrCreate(
            ['email' => 'mazen.alkhatib.devpulse@gmail.com'],
            [
                'name' => 'Dr. Mazen Al Khatib',
                'username' => 'dr_mazen_alkhatib',
                'password' => 'Password@123',
                'email_verified_at' => now(),
                'status' => 'active',
                'profile_completed' => true,
            ]
        );

        $user->syncRoles([$role]);

        CommitteeMemberProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => 'Dr. Mazen Al Khatib',
                'academic_title' => 'Professor',
                'department' => 'Software Engineering',
                'specialization' => 'Software Engineering and Project Evaluation',
                'bio' => 'Committee member responsible for reviewing and evaluating graduation projects.',
            ]
        );
    }
}
