import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:heroicons/heroicons.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/floz_card.dart';

class StudentDashboardScreen extends ConsumerWidget {
  const StudentDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;
    final tenant = ref.watch(authProvider).tenant;

    return Scaffold(
      backgroundColor: AppColors.neutral50,
      body: CustomScrollView(
        slivers: [
          // App Bar with Tenant Logo and Profile
          SliverAppBar(
            backgroundColor: Colors.white,
            floating: true,
            pinned: true,
            elevation: 0,
            title: Row(
              children: [
                if (tenant?['logo_url'] != null)
                  CircleAvatar(
                    backgroundImage: NetworkImage(tenant!['logo_url']),
                    radius: 16,
                  )
                else
                  const CircleAvatar(
                    backgroundColor: AppColors.primary100,
                    radius: 16,
                    child: HeroIcon(
                      HeroIcons.academicCap,
                      style: HeroIconStyle.solid,
                      color: AppColors.primary600,
                      size: 20,
                    ),
                  ),
                const SizedBox(width: 12),
                Text(
                  tenant?['name'] ?? 'Sekolah',
                  style: GoogleFonts.inter(
                    textStyle: const TextStyle(
                      color: AppColors.neutral900,
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                  ),
                ),
              ],
            ),
            actions: [
              IconButton(
                onPressed: () {}, // TODO: Notifications
                icon: const HeroIcon(
                  HeroIcons.bell,
                  style: HeroIconStyle.outline,
                  color: AppColors.neutral600,
                ),
              ),
              const SizedBox(width: 8),
            ],
          ),

          // Welcome Section
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Halo, ${user?.name ?? "Siswa"}!',
                    style: GoogleFonts.inter(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: AppColors.neutral900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Selamat datang kembali.',
                    style: GoogleFonts.inter(
                      fontSize: 14,
                      color: AppColors.neutral500,
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Stats / Quick Actions Grid
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            sliver: SliverGrid.count(
              crossAxisCount: 2,
              mainAxisSpacing: 16,
              crossAxisSpacing: 16,
              childAspectRatio: 1.5,
              children: [
                _buildStatCard(
                  title: 'Tugas',
                  value: '3',
                  subtitle: 'Belum dikumpulkan',
                  icon: HeroIcons.documentText,
                  color: AppColors.primary600,
                  backgroundColor: AppColors.primary50,
                ),
                _buildStatCard(
                  title: 'Jadwal',
                  value: 'Hari Ini',
                  subtitle: '4 Mata Pelajaran',
                  icon: HeroIcons.calendar,
                  color: Colors.orange,
                  backgroundColor: Colors.orange.shade50,
                ),
                _buildStatCard(
                  title: 'Kehadiran',
                  value: '95%',
                  subtitle: 'Semester ini',
                  icon: HeroIcons.checkCircle,
                  color: Colors.green,
                  backgroundColor: Colors.green.shade50,
                ),
                _buildStatCard(
                  title: 'Nilai',
                  value: 'A',
                  subtitle: 'Rata-rata',
                  icon: HeroIcons.chartBar,
                  color: Colors.purple,
                  backgroundColor: Colors.purple.shade50,
                ),
              ],
            ),
          ),

          SliverToBoxAdapter(child: SizedBox(height: 24)),

          // Recent Activity Header
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Text(
                'Aktivitas Terbaru',
                style: GoogleFonts.inter(
                  fontSize: 18,
                  fontWeight: FontWeight.w600,
                  color: AppColors.neutral800,
                ),
              ),
            ),
          ),

          // Placeholder for recent activity list
          SliverList(
            delegate: SliverChildBuilderDelegate((context, index) {
              return Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 8,
                ),
                child: FlozCard(
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: AppColors.neutral100,
                      child: HeroIcon(
                        HeroIcons.clock,
                        color: AppColors.neutral500,
                      ),
                    ),
                    title: Text(
                      'Tugas Matematika',
                      style: GoogleFonts.inter(fontWeight: FontWeight.w500),
                    ),
                    subtitle: Text('Dikumpulkan kemarin'),
                    trailing: Text(
                      'Selesai',
                      style: GoogleFonts.inter(
                        color: Colors.green,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
              );
            }, childCount: 3),
          ),

          SliverToBoxAdapter(
            child: SizedBox(height: 100),
          ), // Bottom padding for nav bar
        ],
      ),
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required String subtitle,
    required HeroIcons icon,
    required Color color,
    required Color backgroundColor,
  }) {
    return FlozCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: backgroundColor,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: HeroIcon(icon, color: color, size: 20),
              ),
              // Optional: Add arrow or menu
            ],
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                value,
                style: GoogleFonts.inter(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: AppColors.neutral900,
                ),
              ),
              Text(
                subtitle,
                style: GoogleFonts.inter(
                  fontSize: 12,
                  color: AppColors.neutral500,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ],
      ),
    );
  }
}
