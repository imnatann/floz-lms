import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../features/auth/presentation/splash_screen.dart';
import '../../features/auth/presentation/tenant_search_screen.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/dashboard/presentation/student_dashboard_screen.dart';
import '../../features/dashboard/presentation/teacher_dashboard_screen.dart';
import '../../features/dashboard/presentation/parent_dashboard_screen.dart';
import '../../features/schedule/presentation/schedule_screen.dart';
import '../../features/assignments/presentation/assignments_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../shared/widgets/floz_bottom_nav.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/',
    debugLogDiagnostics: true,
    redirect: (context, state) {
      final isLoggingIn = state.uri.toString() == '/login';
      final isSearchingTenant = state.uri.toString() == '/tenant-search';
      final isSplash = state.uri.toString() == '/';

      // If loading, show splash
      if (authState.isLoading && isSplash) return null;

      // If no tenant selected, go to tenant search
      if (authState.tenant == null) {
        return isSearchingTenant ? null : '/tenant-search';
      }

      // If tenant selected but not authenticated
      if (!authState.isAuthenticated) {
        return isLoggingIn ? null : '/login';
      }

      // If authenticated, go to dashboard (if currently on login/search/splash)
      if (isLoggingIn || isSearchingTenant || isSplash) {
        return '/dashboard';
      }

      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (context, state) => const SplashScreen()),
      GoRoute(
        path: '/tenant-search',
        builder: (context, state) => const TenantSearchScreen(),
      ),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return FlozBottomNav(navigationShell: navigationShell);
        },
        branches: [
          // Branch 1: Home (Dashboard)
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/dashboard',
                builder: (context, state) {
                  final user = ref.read(authProvider).user;
                  if (user?.isStudent ?? false) {
                    return const StudentDashboardScreen();
                  }
                  if (user?.isTeacher ?? false) {
                    return const TeacherDashboardScreen();
                  }
                  if (user?.isParent ?? false) {
                    return const ParentDashboardScreen();
                  }
                  return const Scaffold(
                    body: Center(child: Text('Unknown Role')),
                  );
                },
              ),
            ],
          ),
          // Branch 2: Schedule
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/schedule',
                builder: (context, state) => const ScheduleScreen(),
              ),
            ],
          ),
          // Branch 3: Assignments
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/assignments',
                builder: (context, state) => const AssignmentsScreen(),
              ),
            ],
          ),
          // Branch 4: Profile
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/profile',
                builder: (context, state) => const ProfileScreen(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
});
