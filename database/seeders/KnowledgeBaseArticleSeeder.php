<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KnowledgeBaseArticle;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;

class KnowledgeBaseArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first teknisi to use as author
        $teknisi = Teknisi::first();
        
        if (!$teknisi) {
            $this->command->warn('No teknisi found. Please seed teknisi first.');
            return;
        }

        // Get some applications and categories
        $aplikasi = Aplikasi::first();
        $kategori = KategoriMasalah::first();

        $articles = [
            [
                'author_nip' => $teknisi->nip,
                'title' => 'How to Reset User Password in System',
                'summary' => 'Step-by-step guide to reset user passwords safely',
                'content' => "This article explains how to reset a user's password in the system.\n\nSteps:\n1. Log in to the admin panel\n2. Navigate to User Management\n3. Search for the user by NIP or email\n4. Click 'Reset Password' button\n5. A temporary password will be sent to user's email\n6. User must change password on first login\n\nSecurity Notes:\n- Never share passwords via phone or unencrypted channels\n- Always verify user identity before resetting\n- Log all password reset activities",
                'aplikasi_id' => $aplikasi?->id,
                'kategori_masalah_id' => $kategori?->id,
                'tags' => ['Password', 'User Management', 'Security', 'Admin'],
                'status' => 'published',
                'is_featured' => true,
                'view_count' => 45,
                'helpful_count' => 12,
            ],
            [
                'author_nip' => $teknisi->nip,
                'title' => 'Troubleshooting Network Connection Issues',
                'summary' => 'Common network problems and how to fix them',
                'content' => "Network connectivity is crucial for system access. Here's how to troubleshoot:\n\n1. Check Physical Connections\n- Verify ethernet cable is properly connected\n- Check if network indicator lights are on\n- Try a different ethernet port\n\n2. IP Configuration\n- Run 'ipconfig' command\n- Verify IP address is in correct range\n- Try releasing and renewing IP: ipconfig /release && ipconfig /renew\n\n3. DNS Issues\n- Ping internal servers by IP\n- If IP works but domain doesn't, check DNS\n- Flush DNS cache: ipconfig /flushdns\n\n4. Firewall\n- Temporarily disable to test (then re-enable)\n- Check if application is allowed through firewall\n\n5. Contact Network Team\n- If none of above works\n- Provide: IP address, subnet mask, gateway, DNS servers\n- Include error messages and screenshots",
                'aplikasi_id' => null,
                'kategori_masalah_id' => null,
                'tags' => ['Network', 'Troubleshooting', 'IP', 'DNS', 'Connectivity'],
                'status' => 'published',
                'is_featured' => true,
                'view_count' => 78,
                'helpful_count' => 23,
            ],
            [
                'author_nip' => $teknisi->nip,
                'title' => 'Database Backup Best Practices',
                'summary' => 'Guidelines for creating and managing database backups',
                'content' => "Regular backups are essential for data protection.\n\nDaily Backup Checklist:\n- Automated backup runs at 2 AM daily\n- Verify backup completion email\n- Check backup file size (should be consistent)\n- Test restore monthly\n\nBackup Types:\n1. Full Backup - Complete database copy (Weekly)\n2. Differential - Changes since last full backup (Daily)\n3. Transaction Log - Continuous\n\nStorage:\n- Primary: Local NAS\n- Secondary: Cloud storage\n- Keep 30 days of backups\n- Quarterly backups kept for 1 year\n\nRestore Procedure:\n1. Notify all users of maintenance\n2. Stop application services\n3. Restore database from backup\n4. Run integrity checks\n5. Test critical functions\n6. Resume services\n7. Monitor for issues\n\nTesting:\n- Monthly restore test on separate server\n- Document restore time\n- Update procedures if needed",
                'aplikasi_id' => $aplikasi?->id,
                'kategori_masalah_id' => null,
                'tags' => ['Database', 'Backup', 'Best Practices', 'Disaster Recovery'],
                'status' => 'published',
                'is_featured' => false,
                'view_count' => 34,
                'helpful_count' => 8,
            ],
            [
                'author_nip' => $teknisi->nip,
                'title' => 'Common Error Codes and Solutions',
                'summary' => 'Quick reference for frequently encountered error codes',
                'content' => "Error Code Reference:\n\nERR_001: Database Connection Failed\n- Check database server status\n- Verify connection string\n- Check firewall rules\n\nERR_002: Authentication Failed\n- Verify username and password\n- Check account status (not locked/disabled)\n- Clear browser cache and cookies\n\nERR_003: Permission Denied\n- User lacks required role\n- Contact admin for role assignment\n- Check group memberships\n\nERR_004: File Upload Failed\n- Check file size (max 2MB per file)\n- Verify file type is allowed\n- Check disk space on server\n\nERR_005: Session Timeout\n- User inactive for >120 minutes\n- Log in again\n- Save work frequently\n\nFor unlisted errors:\n- Note exact error code and message\n- Check system logs\n- Create ticket with details",
                'aplikasi_id' => $aplikasi?->id,
                'kategori_masalah_id' => $kategori?->id,
                'tags' => ['Error Codes', 'Troubleshooting', 'Quick Reference'],
                'status' => 'published',
                'is_featured' => false,
                'view_count' => 56,
                'helpful_count' => 15,
            ],
            [
                'author_nip' => $teknisi->nip,
                'title' => 'New Features Guide (Draft)',
                'summary' => 'Upcoming features in next release',
                'content' => "This is a draft article about upcoming features.\n\nPlanned features:\n- Enhanced reporting\n- Mobile app\n- API integration\n- Advanced search\n\n(More details to be added)",
                'aplikasi_id' => null,
                'kategori_masalah_id' => null,
                'tags' => ['Features', 'Roadmap', 'Coming Soon'],
                'status' => 'draft',
                'is_featured' => false,
                'view_count' => 0,
                'helpful_count' => 0,
            ],
        ];

        foreach ($articles as $articleData) {
            KnowledgeBaseArticle::create($articleData);
        }

        $this->command->info('Knowledge Base articles seeded successfully!');
    }
}
