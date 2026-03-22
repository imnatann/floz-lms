# FLOZ Mobile

This directory contains the Flutter mobile app for FLOZ.

## Purpose

The mobile app is the companion client for school users. The current codebase is aimed at student, teacher, and parent-facing workflows, while the backend API is served from the Laravel app in `src`.

## Stack

- Flutter
- Riverpod
- GoRouter
- Dio
- Hive
- Shared Preferences
- Secure Storage

## Main Areas

- `lib/features/auth` - tenant search, login, auth state
- `lib/features/dashboard` - student, teacher, and parent dashboards
- `lib/features/schedule` - schedule screens
- `lib/features/assignments` - assignment screens
- `lib/features/profile` - profile screens
- `lib/core/network` - API client setup
- `lib/core/constants/api_constants.dart` - API base URL and request timeouts

## Current Status

- The app has real structure and feature screens.
- Some flows are still placeholder-level and depend on backend/API completion.
- `lib/core/constants/api_constants.dart` still uses a localhost base URL, so environment-specific configuration is still needed for real deployments.

## Local Run

From `floz_mobile`:

```bash
flutter pub get
flutter run
```

## Recommended Next Improvements

- Move API base URL into environment or flavor configuration.
- Match each mobile screen to a stable backend API contract.
- Keep the first mobile release scope narrow and fully working before expanding features.
