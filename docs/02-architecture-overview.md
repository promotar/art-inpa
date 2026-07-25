# Z4Rank Architecture Overview

## Purpose

This document defines the high-level architecture of the Z4Rank custom modular platform.

The goal is to keep Laravel as the backend framework while organizing Z4Rank-specific platform behavior into clear layers that can be documented, maintained, extended, and reused across independent client installations.

## Architecture Layers

The platform architecture is organized into the following layers:

1. Laravel Core
2. Platform Core
3. Core Extension Engine
4. Plugin Manager
5. Theme Manager
6. Hook System
7. Page Builder Plugin
8. Business Plugins
9. Frontend and theme layer

These layers must remain separated by clear responsibilities. A layer may use approved extension points from the layer below it, but it must not modify that layer's internal files directly.

## Laravel Core

Laravel is the backend framework and runtime foundation.

Laravel provides routing, requests, validation, middleware, service container, database access, Eloquent ORM, migrations, queues, cache, events, authentication foundations, authorization tools, filesystem support, configuration, testing support, and API foundations.

Laravel core, vendor files, and framework internals must not be modified.

## Platform Core

The Platform Core is the proprietary Z4Rank layer built above Laravel.

It contains shared platform systems, contracts, services, actions, DTOs, support utilities, providers, admin foundations, API foundations, shared behavior, and theme integration rules.

The Platform Core should contain reusable cross-platform behavior only. Client-specific logic and module-specific business behavior should not be placed directly in the Platform Core unless it is truly shared.

## Core Extension Engine

The Core Extension Engine is the controlled extension model that allows plugins, modules, themes, and future platform components to connect to the Platform Core without modifying Laravel or unrelated platform internals.

It should define approved extension points, lifecycle events, contracts, service bindings, hooks, configuration conventions, and registration rules.

This engine protects the platform from uncontrolled changes while still allowing future growth.

## Plugin Manager

The Plugin Manager is responsible for discovering, validating, installing, activating, deactivating, updating, and eventually removing plugins or modules.

It should enforce package structure rules, metadata requirements, lifecycle rules, dependency checks, security restrictions, and compatibility expectations.

Plugins must not directly modify Laravel core, vendor files, or unrelated platform internals.

## Theme Manager

The Theme Manager controls the visual layer of the platform.

It allows each client installation to use a standard or custom theme while keeping backend logic, Platform Core behavior, and business modules stable.

Themes may provide layouts, templates, sections, components, assets, and presentation rules. Themes must not contain core business logic.

## Hook System

The Hook System allows controlled customization at approved points in the platform lifecycle.

Hooks may be used for events, filters, extension points, module registration, menu registration, admin integration, theme overrides, or future plugin behavior.

Hooks must be documented and versioned. A hook should not expose unstable internal implementation details.

## Page Builder Plugin

The Page Builder Plugin is treated as a plugin, not as platform core.

It may provide visual page creation, content sections, draft and published states, preview behavior, menu visibility settings, and page presentation features.

The Page Builder should use Platform Core services, theme rules, permissions, and approved extension points rather than becoming a separate uncontrolled system.

## Business Plugins

Business Plugins provide domain-specific functionality such as Blog, LMS, Store, Exhibition, Booking, Events, or future client-facing systems.

Each business plugin should remain modular and independently manageable. It should define its routes, models, services, actions, policies, permissions, admin resources, API endpoints, migrations, tests, and documentation according to platform standards.

Business plugins should communicate through contracts, events, services, APIs, or shared Platform Core systems rather than directly depending on each other's internal implementation.

## Layer Relationships

The intended direction of dependency is:

Laravel Core -> Platform Core -> Core Extension Engine -> Plugins and Themes -> Frontend/Admin/API presentation.

The dependency flow should remain controlled:

- Laravel provides framework infrastructure.
- Platform Core owns shared Z4Rank logic.
- Core Extension Engine defines safe extension points.
- Plugin Manager controls plugin lifecycle.
- Theme Manager controls visual presentation.
- Hook System provides approved customization points.
- Page Builder Plugin uses the platform as a plugin.
- Business Plugins provide focused domain features.
- Frontend, Admin, and API layers present or expose approved functionality.

## Implementation Rule

This document belongs to the documentation phase.

No functional code should be implemented because of this document alone. Any future implementation must be approved as a separate task, documented, and tracked in the checklist.

## Arabic Summary

توضح هذه الوثيقة النظرة المعمارية العامة لمنصة Z4Rank. تعتمد المنصة على Laravel كإطار خلفي ثابت، ثم تضيف فوقه طبقة Platform Core الخاصة بـ Z4Rank، وبعدها نموذج تمديد مضبوط يشمل Core Extension Engine، Plugin Manager، Theme Manager، Hook System، Page Builder Plugin، وBusiness Plugins.

يجب أن تبقى العلاقة بين الطبقات واضحة ومضبوطة. Laravel يوفر الأساس التقني، وPlatform Core يحتوي المنطق المشترك، وCore Extension Engine يحدد نقاط التمديد، وPlugin Manager يدير دورة حياة الإضافات، وTheme Manager يدير العرض البصري، وHook System يسمح بالتخصيص الموثق، بينما تبقى Page Builder وBusiness Plugins وحدات قابلة للإدارة وليست تعديلات مباشرة على Laravel أو على نواة المنصة.

هذه الوثيقة للتوثيق فقط، ولا تسمح بتنفيذ كود فعلي قبل اعتماد تاسك منفصل.
