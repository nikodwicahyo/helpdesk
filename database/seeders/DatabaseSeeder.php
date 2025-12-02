<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting HelpDesk Kemlu Database Seeding...');

        $this->command->info('👥 Seeding regular users...');
        $this->call(UserSeeder::class);

        $this->command->info('🛠️ Seeding helpdesk administrators...');
        $this->call(AdminHelpdeskSeeder::class);

        $this->command->info('⚙️ Seeding application administrators...');
        $this->call(AdminAplikasiSeeder::class);

        $this->command->info('🔧 Seeding technical support staff...');
        $this->call(TeknisiSeeder::class);

        $this->command->info('📱 Seeding applications with admin assignments...');
        $this->call(AplikasiSeeder::class);

        $this->command->info('📂 Seeding problem categories...');
        $this->call(KategoriMasalahSeeder::class);

        $this->command->info('🎫 Seeding tickets...');
        $this->call(TicketSeeder::class);

        $this->command->info('💬 Seeding ticket comments...');
        $this->call(TicketCommentSeeder::class);

        $this->command->info('📝 Seeding audit logs...');
        $this->call(AuditLogSeeder::class);

        $this->command->info('🔔 Seeding notifications...');
        $this->call(NotificationSeeder::class);

        $this->command->info('📊 Seeding reports...');
        $this->call(ReportSeeder::class);

        $this->command->info('⚙️ Seeding system settings...');
        $this->call(SystemSettingSeeder::class);

        $this->command->info('✅ HelpDesk Kemlu Database Seeding Completed Successfully!');
        $this->command->info('');
        $this->command->info('📋 Summary of seeded data:');
        $this->command->info('  • 10 Government Applications');
        $this->command->info('  • 40+ Problem Categories');
        $this->command->info('  • 25 Regular Users');
        $this->command->info('  • 5 Helpdesk Administrators');
        $this->command->info('  • 8 Application Administrators');
        $this->command->info('  • 10 Technical Support Staff');
        $this->command->info('  • 10 Sample Tickets');
        $this->command->info('  • 15+ Ticket Comments');
        $this->command->info('  • 200+ Audit Log Entries (login, ticket lifecycle, user management)');
        $this->command->info('  • 12 Notifications');
        $this->command->info('  • 4 Sample Reports');
        $this->command->info('');
        $this->command->info('🔐 Default login credentials:');
        $this->command->info('  • Regular User: password123');
        $this->command->info('  • Admin Helpdesk: admin123');
        $this->command->info('  • Admin Aplikasi: admin123');
        $this->command->info('  • Teknisi: password123');
    }
}
