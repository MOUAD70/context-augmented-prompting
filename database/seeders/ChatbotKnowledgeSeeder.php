<?php

namespace Database\Seeders;

use App\Models\ChatbotKnowledge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatbotKnowledgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ChatbotKnowledge::insert([
            [
                'title' => 'Incident Reporting Procedure',
                'category' => 'incident',
                'content' => 'Any SSIAP1/SSIAP2 must report incidents immediately. Create an incident from the Incidents page. Fill type, location, description and urgency. SSIAP2 validates first, then SSIAP3 approves.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Vacation Approval Rules',
                'category' => 'vacation',
                'content' => 'Vacation requests must be submitted at least 7 days before the start date. SSIAP2 can approve SSIAP1 vacations. SSIAP3 approves SSIAP2 vacations and all vacations for critical sites.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Shift Attendance Policy',
                'category' => 'attendance',
                'content' => 'Employees must confirm their presence within 15 minutes after shift start. If not confirmed, the system marks the employee as Absent. SSIAP2 can assign replacement for SSIAP1. SSIAP3 can assign replacement for SSIAP2.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Equipment Maintenance',
                'category' => 'equipment',
                'content' => 'Fire extinguishers must be checked monthly. If an extinguisher is expired, create a maintenance report. SSIAP2 can create maintenance tasks. SSIAP3 validates maintenance completion.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
