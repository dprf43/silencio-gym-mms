<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Member;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            [
                'uid' => 'UID001',
                'member_number' => 'MEM001',
                'membership' => 'premium',
                'full_name' => 'John Doe',
                'mobile_number' => '+1 (555) 123-4567',
                'email' => 'john.doe@example.com',
            ],
            [
                'uid' => 'UID002',
                'member_number' => 'MEM002',
                'membership' => 'basic',
                'full_name' => 'Jane Smith',
                'mobile_number' => '+1 (555) 234-5678',
                'email' => 'jane.smith@example.com',
            ],
            [
                'uid' => 'UID003',
                'member_number' => 'MEM003',
                'membership' => 'vip',
                'full_name' => 'Mike Johnson',
                'mobile_number' => '+1 (555) 345-6789',
                'email' => 'mike.johnson@example.com',
            ],
            [
                'uid' => 'UID004',
                'member_number' => 'MEM004',
                'membership' => 'premium',
                'full_name' => 'Sarah Wilson',
                'mobile_number' => '+1 (555) 456-7890',
                'email' => 'sarah.wilson@example.com',
            ],
            [
                'uid' => 'UID005',
                'member_number' => 'MEM005',
                'membership' => 'basic',
                'full_name' => 'David Brown',
                'mobile_number' => '+1 (555) 567-8901',
                'email' => 'david.brown@example.com',
            ],
        ];

        foreach ($members as $memberData) {
            Member::create($memberData);
        }
    }
}
