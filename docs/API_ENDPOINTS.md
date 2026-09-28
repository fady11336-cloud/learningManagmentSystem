# Learning Management System — API Endpoint Specification

> Status: **Design only.** No application code is written by this document.
> Source of truth: `docs/buisness_requirments.html` (BRD + PRD) and the existing
> migrations / Eloquent models. Where the requirements and the schema disagree, the
> conflict is recorded as **NEEDS DECISION** and the application code is never modified.
>
> The BRD/PRD file is named `buisness_requirments.html` (misspelled) — the task brief
> referenced `business-requirements.html`, which does not exist.

---

## 0. Conventions & design baseline

### Transport, auth, and roles

| Concern | Convention |
| --- | --- |
| Base URL | `/api` (e.g. `GET /api/courses`). JWT via `tymon/jwt-auth`. |
| Auth | `Authorization: Bearer <token>`. Guard: `auth:api` (JWT) — **not wired today** → NEEDS DECISION (Authentication). |
| Roles | `Admin`, `Student`, `Instructor` (DB enum). `Guest` = unauthenticated, not a DB role. |
| Ownership | `Self` = the user owns the record; `Course-owner` = the instructor who owns the parent course or an Admin. Admin bypasses all ownership checks. |
| Email verification | Gates enroll, submit, attempt, publish (equivalent of `verified` gate). |

In the endpoint tables the **Auth** column is `—` for public/Guest endpoints and `✓` for
authenticated endpoints. `—` anywhere else means *not applicable*.

### Responses and errors

| Code | Meaning |
| --- | --- |
| `200` | OK (actions, updates, reads) |
| `201` | Created |
| `204` | Deleted |
| `401` | Unauthenticated (missing/invalid token or credentials) |
| `403` | Wrong role, or not the resource/course owner |
| `404` | Resource not found |
| `409` | Conflict / wrong lifecycle state (duplicate enroll, duplicate title, already published) |
| `422` | Validation or business-rule failure (`errors` keyed by field) |

Shared response envelope: `{ data, message, errors, meta }`. Lists are paginated with `meta`
(`current_page`, `per_page`, `total`, `last_page`).

**Except where a module explicitly says otherwise, every endpoint follows these conventions** —
they are not repeated per endpoint:
- read list → `200` + paginated `data`;
- create → `201`; update/action → `200`; delete → `204`;
- any mutation that fails validation → `422`; missing resource → `404`;
- authenticated endpoints → `401` on missing/invalid token.

### Query conventions

- **Pagination:** `?page=1`; optional `?per_page=` (capped at 50).
- **Filtering/search/sort** (on lists that support it): `q` (free-text search), `status`,
  `category_id`, `instructor_id`, `course_id`, `from`, `to`, `sort` (e.g. `-created_at`).

### Endpoint taxonomy

- **CRUD endpoints** — resource lifecycle (list, show, create, update, delete). Includes read-only
  query endpoints (reports).
- **Business / action endpoints** — a meaningful domain action that a plain resource update cannot
  express (publish, archive, enroll, submit, grade, attempt, reorder, mark-read, …).

### Class naming conventions

These classes do **not exist yet** — they are the intended implementation targets referenced in the
endpoint details.

- Controllers: `AuthController`, `UserController`, `CategoryController`, `CourseController`, `LessonController`,
  `EnrollmentController`, `AssignmentController`, `SubmissionController`, `QuizController`, `QuestionController`,
  `AttemptController`, `ProgressController`, `CertificateController`, `NotificationController`, `ReportController`.
- Resources: `UserResource`, `RoleResource`, `CategoryResource`, `CourseResource`, `LessonResource`,
  `EnrollmentResource`, `AssignmentResource`, `SubmissionResource`, `QuizResource`, `QuestionResource`,
  `AttemptResource`, `ProgressResource`, `CertificateResource`, `NotificationResource`, `ReportResource`.
- Policies: `UserPolicy`, `CategoryPolicy`, `CoursePolicy`, `LessonPolicy`, `EnrollmentPolicy`,
  `AssignmentPolicy`, `SubmissionPolicy`, `QuizPolicy`, `QuestionPolicy`, `AttemptPolicy`, `ProgressPolicy`,
  `CertificatePolicy`, `NotificationPolicy`, `ReportPolicy`.
- Services: `AuthService`, `UserService`, `CourseService`, `LessonService`, `EnrollmentService`,
  `AssignmentService`, `SubmissionService`, `QuizService`, `QuestionService`, `AttemptService`,
  `ProgressService`, `CertificateService`, `NotificationService`, `ReportService`.

---

## 1. Authentication & Authorization (8 endpoints)

Registration, login, email verification, password reset (uses existing `password_reset_tokens`).
Email verification is required **before enrolling or publishing**; roles are fixed at registration
and only an Admin may change them. All 8 endpoints are business/action endpoints — there is no CRUD
table here.

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| POST | `/auth/register` | — | Guest | — | `AuthController@register` | `RegisterRequest` | — | `AuthService` | `UserResource` |
| POST | `/auth/login` | — | Guest | — | `AuthController@login` | `LoginRequest` | — | `AuthService` | `UserResource` |
| POST | `/auth/logout` | ✓ | all | — | `AuthController@logout` | `LogoutRequest` | — | `AuthService` | — |
| POST | `/auth/refresh` | ✓ | all | — | `AuthController@refresh` | — | — | `AuthService` | — |
| POST | `/auth/email/verify` | ✓ | all | — | `AuthController@verifyEmail` | `VerifyEmailRequest` | — | `AuthService` | — |
| POST | `/auth/email/resend` | ✓ | all | — | `AuthController@resendVerification` | `VerifyEmailRequest` | — | `AuthService` | — |
| POST | `/auth/password/forgot` | — | Guest | — | `AuthController@forgotPassword` | `ForgotPasswordRequest` | — | `AuthService` | — |
| POST | `/auth/password/reset` | — | Guest | — | `AuthController@resetPassword` | `ResetPasswordRequest` | — | `AuthService` | — |

### Endpoint Details

### `POST /auth/register`

**Purpose:** create an account with a fixed role and an unverified email; returns a JWT.

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `AuthController@register`
**Form Request:** `RegisterRequest`
**Policy:** —
**Service:** `AuthService`
**Resource:** `UserResource`

**Request body / Parameters:** `name` (required, ≤255), `email` (required, valid, unique in `users`),
`password` (required, ≥8, confirmed), `role` (required: `Admin|Student|Instructor`).

**Validation:** required fields, email format + uniqueness, password length + confirmation, role must
be a DB role (`Guest` rejected).

**Business Rules:**
- roles are fixed at registration;
- email starts unverified; unverified accounts cannot enroll/submit/attempt/publish;
- whether a Guest may self-register as `Instructor` → Assumptions + NEEDS DECISION (Authentication).

**Response:** `201` — `UserResource` + `data.token`; `422` duplicate email / weak password / invalid role.

### `POST /auth/login`

**Purpose:** authenticate and receive a JWT.

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `AuthController@login`
**Form Request:** `LoginRequest`
**Policy:** —
**Service:** `AuthService`
**Resource:** `UserResource`

**Request body / Parameters:** `email`, `password`.

**Business Rules:**
- login does **not** require a verified email — verification gates specific actions, not login.

**Response:** `200` — `UserResource` + `data.token`; `401` invalid credentials.

### `POST /auth/logout`

**Purpose:** invalidate the current session.

**Authentication:** Required
**Roles:** all
**Ownership:** —

**Controller:** `AuthController@logout`
**Form Request:** `LogoutRequest`
**Policy:** —
**Service:** `AuthService`
**Resource:** —

**Business Rules:**
- logout blacklists/invalidates the current JWT.

**Response:** `200`.

### `POST /auth/refresh`

**Purpose:** exchange an expiring token for a fresh one.

**Authentication:** Required
**Roles:** all
**Ownership:** —

**Controller:** `AuthController@refresh`
**Form Request:** —
**Policy:** —
**Service:** `AuthService`
**Resource:** —

**Business Rules:**
- refresh returns the new token; requires authentication.

**Response:** `200` — new token.

### `POST /auth/email/verify`

**Purpose:** confirm the account email.

**Authentication:** Required
**Roles:** all
**Ownership:** —

**Controller:** `AuthController@verifyEmail`
**Form Request:** `VerifyEmailRequest`
**Policy:** —
**Service:** `AuthService`
**Resource:** —

**Request body / Parameters:** `token`.

**Business Rules:**
- verify accepts `token` and sets `users.email_verified_at`.

**Edge Cases:**
- already-verified user verifying again → idempotent `200` (or `409`, decide).

**Response:** `200`; `422` invalid/expired verification token.

### `POST /auth/email/resend`

**Purpose:** re-send the verification notification.

**Authentication:** Required
**Roles:** all
**Ownership:** —

**Controller:** `AuthController@resendVerification`
**Form Request:** `VerifyEmailRequest`
**Policy:** —
**Service:** `AuthService`
**Resource:** —

**Business Rules:**
- resend re-sends the verification notification (only useful while unverified).

**Response:** `200`.

### `POST /auth/password/forgot`

**Purpose:** request a password reset link.

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `AuthController@forgotPassword`
**Form Request:** `ForgotPasswordRequest`
**Policy:** —
**Service:** `AuthService`
**Resource:** —

**Request body / Parameters:** `email`.

**Business Rules:**
- always returns `200` (anti user-enumeration).

**Response:** `200`.

### `POST /auth/password/reset`

**Purpose:** set a new password with a reset token.

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `AuthController@resetPassword`
**Form Request:** `ResetPasswordRequest`
**Policy:** —
**Service:** `AuthService`
**Resource:** —

**Request body / Parameters:** `email`, `token`, `password`, `password_confirmation`.

**Validation:** weak password rejected; invalid/expired reset token rejected.

**Response:** `200`; `422` invalid/expired reset token, weak password.---

## 2. Users (6 endpoints)

Self-service for Students/Instructors plus Admin user management. Role changes are Admin-only.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/users/me` | ✓ | all | Self | `UserController@me` | — | `UserPolicy@viewOwn` | `UserService` | `UserResource` |
| PUT | `/users/me` | ✓ | all | Self | `UserController@updateOwn` | `UpdateProfileRequest` | `UserPolicy@updateOwn` | `UserService` | `UserResource` |
| GET | `/users` | ✓ | Admin | — | `UserController@index` | `IndexUserRequest` | `UserPolicy@viewAny` | — | `UserResource` |
| GET | `/users/{user}` | ✓ | Admin | — | `UserController@show` | — | `UserPolicy@view` | — | `UserResource` |
| PUT | `/users/{user}` | ✓ | Admin | — | `UserController@update` | `UpdateUserRequest` | `UserPolicy@update` | `UserService` | `UserResource` |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| PUT | `/users/{user}/role` | ✓ | Admin | — | `UserController@changeRole` | `UpdateUserRoleRequest` | `UserPolicy@changeRole` | `UserService` | `UserResource` |

### Endpoint Details

### `GET /users/me`

**Purpose:** view the current profile.

**Authentication:** Required
**Roles:** all
**Ownership:** Self

**Controller:** `UserController@me`
**Form Request:** —
**Policy:** `UserPolicy@viewOwn`
**Service:** `UserService`
**Resource:** `UserResource`

**Response:** `200` — `UserResource`.

### `PUT /users/me`

**Purpose:** update the current profile.

**Authentication:** Required
**Roles:** all
**Ownership:** Self

**Controller:** `UserController@updateOwn`
**Form Request:** `UpdateProfileRequest`
**Policy:** `UserPolicy@updateOwn`
**Service:** `UserService`
**Resource:** `UserResource`

**Request body / Parameters:** `name`, optional `email`, optional `password` (confirmed, ≥8).

**Business Rules:**
- changing email should re-mark the account unverified → NEEDS DECISION (Authentication).

**Response:** `200` — `UserResource`.

### `GET /users`

**Purpose:** Admin user directory.

**Authentication:** Required
**Roles:** Admin
**Ownership:** —

**Controller:** `UserController@index`
**Form Request:** `IndexUserRequest`
**Policy:** `UserPolicy@viewAny` (Admin)
**Service:** —
**Resource:** `UserResource`

**Request body / Parameters (query):** `q` (name/email), `role`, `verified`, `sort`, `page`, `per_page`.

**Response:** `200` — paginated `UserResource` list.

### `GET /users/{user}`

**Purpose:** view a single user (Admin).

**Authentication:** Required
**Roles:** Admin
**Ownership:** —

**Controller:** `UserController@show`
**Form Request:** —
**Policy:** `UserPolicy@view` (Admin)
**Service:** —
**Resource:** `UserResource`

**Response:** `200` — `UserResource`; `404` missing.

### `PUT /users/{user}`

**Purpose:** Admin user management.

**Authentication:** Required
**Roles:** Admin
**Ownership:** —

**Controller:** `UserController@update`
**Form Request:** `UpdateUserRequest`
**Policy:** `UserPolicy@update` (Admin)
**Service:** `UserService`
**Resource:** `UserResource`

**Request body / Parameters:** name/email/verification state.

**Response:** `200` — `UserResource`.

### `PUT /users/{user}/role`

**Purpose:** change a user's role — the **only** place roles may change.

**Authentication:** Required
**Roles:** Admin
**Ownership:** —

**Controller:** `UserController@changeRole`
**Form Request:** `UpdateUserRoleRequest`
**Policy:** `UserPolicy@changeRole`
**Service:** `UserService`
**Resource:** `UserResource`

**Request body / Parameters:** `role` (in `Admin|Student|Instructor`).

**Validation:** role must be one of the three DB roles.

**Edge Cases:**
- disallow demoting the last Admin (decide);
- role change must preserve `courses.user_id` ownership semantics.

**Response:** `200` — `UserResource`.

---

## 3. Categories (5 endpoints)

Admin-managed platform taxonomy (assumed Admin — see Assumptions). Categories are required when
creating a course; guests may browse them. All 5 endpoints are CRUD.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/categories` | — | Guest | — | `CategoryController@index` | — | — | — | `CategoryResource` |
| GET | `/categories/{category}` | — | Guest | — | `CategoryController@show` | — | — | — | `CategoryResource` |
| POST | `/categories` | ✓ | Admin | — | `CategoryController@store` | `StoreCategoryRequest` | `CategoryPolicy@create` | — | `CategoryResource` |
| PUT | `/categories/{category}` | ✓ | Admin | — | `CategoryController@update` | `UpdateCategoryRequest` | `CategoryPolicy@update` | — | `CategoryResource` |
| DELETE | `/categories/{category}` | ✓ | Admin | — | `CategoryController@destroy` | — | `CategoryPolicy@delete` | — | — |

### Endpoint Details

### `GET /categories`

**Purpose:** public category list.

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `CategoryController@index`
**Form Request:** —
**Policy:** —
**Service:** —
**Resource:** `CategoryResource`

**Request body / Parameters (query):** `q`, `sort`, `page`, `per_page`.

**Edge Cases:**
- categories with zero courses are still returned.

**Response:** `200` — paginated `CategoryResource` list.

### `GET /categories/{category}`

**Purpose:** public category detail (includes published courses).

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `CategoryController@show`
**Form Request:** —
**Policy:** —
**Service:** —
**Resource:** `CategoryResource`

**Response:** `200` — `CategoryResource`.

### `POST /categories`

**Purpose:** create a category (Admin).

**Authentication:** Required
**Roles:** Admin
**Ownership:** —

**Controller:** `CategoryController@store`
**Form Request:** `StoreCategoryRequest`
**Policy:** `CategoryPolicy@create`
**Service:** —
**Resource:** `CategoryResource`

**Request body / Parameters:** `name` (required, unique), `description` (required, string).

**Validation:** `422` duplicate name.

**Response:** `201` — `CategoryResource`.

### `PUT /categories/{category}`

**Purpose:** update a category (Admin).

**Authentication:** Required
**Roles:** Admin
**Ownership:** —

**Controller:** `CategoryController@update`
**Form Request:** `UpdateCategoryRequest`
**Policy:** `CategoryPolicy@update`
**Service:** —
**Resource:** `CategoryResource`

**Request body / Parameters:** `name`, `description`.

**Validation:** `422` duplicate name.

**Response:** `200` — `CategoryResource`.

### `DELETE /categories/{category}`

**Purpose:** delete a category (Admin).

**Authentication:** Required
**Roles:** Admin
**Ownership:** —

**Controller:** `CategoryController@destroy`
**Form Request:** —
**Policy:** `CategoryPolicy@delete`
**Service:** —
**Resource:** —

**Edge Cases:**
- deleting a category still referenced by courses → `409` (block vs. reclassify) → NEEDS DECISION (Categories).

**Response:** `204`.

---

## 4. Courses (8 endpoints)

Course lifecycle: **draft → published → archived**. Only instructors create courses; published courses
are public, drafts/archives are not. Only the owning instructor or Admin may mutate.

**Schema conflict:** `courses.status` enum is `['completed','in_progress','not_started']` — progress-ish
states, **not** the lifecycle the PRD requires. The publish/archive design below assumes a proper course
lifecycle status exists → NEEDS DECISION (Courses).

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/courses` | — | Guest | — | `CourseController@index` | `IndexCourseRequest` | — | `CourseService` | `CourseResource` |
| GET | `/courses/{course}` | — | Guest | — | `CourseController@show` | — | `CoursePolicy@view` | `CourseService` | `CourseResource` |
| POST | `/courses` | ✓ | Instructor | — | `CourseController@store` | `StoreCourseRequest` | `CoursePolicy@create` | `CourseService` | `CourseResource` |
| PUT | `/courses/{course}` | ✓ | Instructor | Course-owner | `CourseController@update` | `UpdateCourseRequest` | `CoursePolicy@update` | `CourseService` | `CourseResource` |
| DELETE | `/courses/{course}` | ✓ | Instructor | Course-owner | `CourseController@destroy` | — | `CoursePolicy@delete` | `CourseService` | — |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| POST | `/courses/{course}/publish` | ✓ | Instructor | Course-owner | `CourseController@publish` | — | `CoursePolicy@publish` | `CourseService` | `CourseResource` |
| POST | `/courses/{course}/archive` | ✓ | Instructor | Course-owner | `CourseController@archive` | — | `CoursePolicy@archive` | `CourseService` | `CourseResource` |
| POST | `/courses/{course}/unarchive` | ✓ | Instructor | Course-owner | `CourseController@unarchive` | — | `CoursePolicy@unarchive` | `CourseService` | `CourseResource` |

### Endpoint Details

### `GET /courses`

**Purpose:** public course catalog (published only).

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `CourseController@index`
**Form Request:** `IndexCourseRequest`
**Policy:** —
**Service:** `CourseService`
**Resource:** `CourseResource`

**Request body / Parameters (query):** `q`, `category_id`, `instructor_id`, `status`, `sort`, `page`, `per_page`.

**Business Rules:**
- visibility: `draft`/`archived` hidden from guests and students.

**Response:** `200` — paginated `CourseResource` list.

### `GET /courses/{course}`

**Purpose:** public course detail.

**Authentication:** Public
**Roles:** Guest
**Ownership:** —

**Controller:** `CourseController@show`
**Form Request:** —
**Policy:** `CoursePolicy@view`
**Service:** `CourseService`
**Resource:** `CourseResource`

**Business Rules:**
- visibility: public only if published; enrolled students additionally see the lesson list; owner/Admin see full internals.

**Response:** `200` — `CourseResource`.

### `POST /courses`

**Purpose:** create a course (Instructor).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** —

**Controller:** `CourseController@store`
**Form Request:** `StoreCourseRequest`
**Policy:** `CoursePolicy@create`
**Service:** `CourseService`
**Resource:** `CourseResource`

**Request body / Parameters:** `title` (required, ≤255), `description` (required), `category_id` (required, exists), optional `status`.

**Validation:** required fields; `category_id` must exist; duplicate `title` per instructor rejected (`409`/`422`) — no DB unique constraint → NEEDS DECISION (Courses).

**Business Rules:**
- starts as `draft`;
- the current `status` enum holds only progress states; creation must not set an invalid value.

**Response:** `201` — `CourseResource`.

### `PUT /courses/{course}`

**Purpose:** update a course (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `CourseController@update`
**Form Request:** `UpdateCourseRequest`
**Policy:** `CoursePolicy@update` (owner/Admin)
**Service:** `CourseService`
**Resource:** `CourseResource`

**Request body / Parameters:** title/description/category.

**Errors:** `403` non-owner, `404` missing.

**Response:** `200` — `CourseResource`.

### `DELETE /courses/{course}`

**Purpose:** delete a course (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `CourseController@destroy`
**Form Request:** —
**Policy:** `CoursePolicy@delete`
**Service:** `CourseService`
**Resource:** —

**Edge Cases:**
- deleting a course with active students → the schema cascades, silently wiping enrollments/progress/attempts. Decide: block (`409`) / archive-only / soft-delete → NEEDS DECISION (Courses).

**Response:** `204`.

### `POST /courses/{course}/publish`

**Purpose:** move a course to published.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `CourseController@publish`
**Form Request:** —
**Policy:** `CoursePolicy@publish`
**Service:** `CourseService`
**Resource:** `CourseResource`

**Business Rules:**
- requires verified instructor email + a category;
- `409` if already published, `422` if incomplete;
- owner/Admin only.

**Response:** `200` — `CourseResource`.

### `POST /courses/{course}/archive`

**Purpose:** archive a course (no new enrollments).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `CourseController@archive`
**Form Request:** —
**Policy:** `CoursePolicy@archive`
**Service:** `CourseService`
**Resource:** `CourseResource`

**Business Rules:**
- archive → **no new enrollments**; existing enrollments/progress remain untouched;
- owner/Admin only.

**Response:** `200` — `CourseResource`.

### `POST /courses/{course}/unarchive`

**Purpose:** bring an archived course back to draft.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `CourseController@unarchive`
**Form Request:** —
**Policy:** `CoursePolicy@unarchive`
**Service:** `CourseService`
**Resource:** `CourseResource`

**Business Rules:**
- unarchive → back to `draft` (never auto-published);
- owner/Admin only.

**Response:** `200` — `CourseResource`.---

## 5. Lessons (6 endpoints)

Ordered content units inside a course. Lesson content is only reachable when the parent course is
published — the course publish is the single gate.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/courses/{course}/lessons` | ✓ | Student/Instructor | Enrolled or Course-owner | `LessonController@index` | — | `LessonPolicy@viewAny` | — | `LessonResource` |
| GET | `/lessons/{lesson}` | ✓ | Student/Instructor | Enrolled or Course-owner | `LessonController@show` | — | `LessonPolicy@view` | — | `LessonResource` |
| POST | `/courses/{course}/lessons` | ✓ | Instructor | Course-owner | `LessonController@store` | `StoreLessonRequest` | `LessonPolicy@create` | `LessonService` | `LessonResource` |
| PUT | `/lessons/{lesson}` | ✓ | Instructor | Course-owner | `LessonController@update` | `UpdateLessonRequest` | `LessonPolicy@update` | `LessonService` | `LessonResource` |
| DELETE | `/lessons/{lesson}` | ✓ | Instructor | Course-owner | `LessonController@destroy` | — | `LessonPolicy@delete` | `LessonService` | — |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| PUT | `/courses/{course}/lessons/reorder` | ✓ | Instructor | Course-owner | `LessonController@reorder` | `ReorderLessonsRequest` | `LessonPolicy@reorder` | `LessonService` | `LessonResource` |

### Endpoint Details

### `GET /courses/{course}/lessons`

**Purpose:** ordered lesson list for enrolled students + course owner/Admin.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Enrolled or Course-owner

**Controller:** `LessonController@index`
**Form Request:** —
**Policy:** `LessonPolicy@viewAny`
**Service:** —
**Resource:** `LessonResource`

**Request body / Parameters (query):** `sort` (default `order asc`), `page`, `per_page`.

**Edge Cases:**
- lesson in a draft course → `403` unless owner/Admin.

**Response:** `200` — paginated `LessonResource` list.

### `GET /lessons/{lesson}`

**Purpose:** single lesson body for enrolled students + course owner/Admin.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Enrolled or Course-owner

**Controller:** `LessonController@show`
**Form Request:** —
**Policy:** `LessonPolicy@view`
**Service:** —
**Resource:** `LessonResource`

**Edge Cases:**
- lesson in a draft course → `403` unless owner/Admin.

**Response:** `200` — `LessonResource`.

### `POST /courses/{course}/lessons`

**Purpose:** add a lesson to a course (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `LessonController@store`
**Form Request:** `StoreLessonRequest`
**Policy:** `LessonPolicy@create`
**Service:** `LessonService`
**Resource:** `LessonResource`

**Request body / Parameters:** `title`, `description`, `content`, optional `duration` (int, nullable), optional `order` (int).

**Business Rules:**
- `order` defaults to `MAX(order)+1` within the course.

**Response:** `201` — `LessonResource`.

### `PUT /lessons/{lesson}`

**Purpose:** update a lesson (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `LessonController@update`
**Form Request:** `UpdateLessonRequest`
**Policy:** `LessonPolicy@update`
**Service:** `LessonService`
**Resource:** `LessonResource`

**Request body / Parameters:** content/order.

**Business Rules:**
- single-lesson order change; bulk repositioning uses the reorder endpoint.

**Response:** `200` — `LessonResource`.

### `DELETE /lessons/{lesson}`

**Purpose:** delete a lesson (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `LessonController@destroy`
**Form Request:** —
**Policy:** `LessonPolicy@delete`
**Service:** `LessonService`
**Resource:** —

**Edge Cases:**
- deleting a lesson students already completed cascade-deletes `progresses` rows. Decide: block / soft-delete / preserve → NEEDS DECISION (Lessons/Progress).

**Response:** `204`.

### `PUT /courses/{course}/lessons/reorder`

**Purpose:** bulk-reposition lessons in a course.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `LessonController@reorder`
**Form Request:** `ReorderLessonsRequest`
**Policy:** `LessonPolicy@reorder`
**Service:** `LessonService`
**Resource:** `LessonResource`

**Request body / Parameters:** `lesson_ids` (ordered, must contain **exactly** the course's lessons).

**Validation:** `lesson_ids` must match exactly the course's lesson set.

**Business Rules:**
- reordering must not break existing `progresses` rows (they key on `lesson_id`, so it is safe).

**Response:** `200` — `LessonResource`.

---

## 6. Enrollments (4 endpoints)

One enrollment per student per course; PRD edge cases demand duplicate-enrollment protection.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/users/me/enrollments` | ✓ | Student | Self | `EnrollmentController@myIndex` | — | `EnrollmentPolicy@viewOwn` | — | `EnrollmentResource` |
| GET | `/enrollments/{enrollment}` | ✓ | Student/Instructor | Self or Course-owner | `EnrollmentController@show` | — | `EnrollmentPolicy@view` | — | `EnrollmentResource` |
| GET | `/courses/{course}/enrollments` | ✓ | Instructor | Course-owner | `EnrollmentController@courseIndex` | — | `EnrollmentPolicy@viewCourse` | — | `EnrollmentResource` |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| POST | `/courses/{course}/enroll` | ✓ | Student | — | `EnrollmentController@store` | `EnrollRequest` | `EnrollmentPolicy@create` | `EnrollmentService` | `EnrollmentResource` |

### Endpoint Details

### `POST /courses/{course}/enroll`

**Purpose:** enroll the current student in a course.

**Authentication:** Required
**Roles:** Student
**Ownership:** —

**Controller:** `EnrollmentController@store`
**Form Request:** `EnrollRequest`
**Policy:** `EnrollmentPolicy@create`
**Service:** `EnrollmentService`
**Resource:** `EnrollmentResource`

**Business Rules:**
- verified email;
- course **published** (not archived);
- one enrollment per student;
- `enrolled_at` is server-set (never user-supplied).

**Edge Cases:**
- duplicate or concurrent enroll → `409`; requires unique composite index `(user_id, course_id)` (none today) → NEEDS DECISION (Enrollments).

**Response:** `201` — `EnrollmentResource`.

### `GET /users/me/enrollments`

**Purpose:** the student's own enrollments.

**Authentication:** Required
**Roles:** Student
**Ownership:** Self

**Controller:** `EnrollmentController@myIndex`
**Form Request:** —
**Policy:** `EnrollmentPolicy@viewOwn`
**Service:** —
**Resource:** `EnrollmentResource`

**Request body / Parameters (query):** `course_id`, `sort`, `page`, `per_page`.

**Business Rules:**
- response includes course + progress summary.

**Response:** `200` — paginated `EnrollmentResource` list.

### `GET /enrollments/{enrollment}`

**Purpose:** view a single enrollment.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Self or Course-owner

**Controller:** `EnrollmentController@show`
**Form Request:** —
**Policy:** `EnrollmentPolicy@view`
**Service:** —
**Resource:** `EnrollmentResource`

**Business Rules:**
- ownership: the owning student, the course instructor, or Admin.

**Response:** `200` — `EnrollmentResource`.

### `GET /courses/{course}/enrollments`

**Purpose:** instructor roster for a course.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `EnrollmentController@courseIndex`
**Form Request:** —
**Policy:** `EnrollmentPolicy@viewCourse`
**Service:** —
**Resource:** `EnrollmentResource`

**Request body / Parameters (query):** `page`, `per_page`.

**Business Rules:**
- owner/Admin only.

**Response:** `200` — paginated `EnrollmentResource` list.

---

## 7. Assignments (5 endpoints)

Instructor-defined tasks with a due date, per course. All 5 endpoints are CRUD.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/courses/{course}/assignments` | ✓ | Student/Instructor | Enrolled or Course-owner | `AssignmentController@index` | — | `AssignmentPolicy@viewAny` | — | `AssignmentResource` |
| GET | `/assignments/{assignment}` | ✓ | Student/Instructor | Enrolled or Course-owner | `AssignmentController@show` | — | `AssignmentPolicy@view` | — | `AssignmentResource` |
| POST | `/courses/{course}/assignments` | ✓ | Instructor | Course-owner | `AssignmentController@store` | `StoreAssignmentRequest` | `AssignmentPolicy@create` | `AssignmentService` | `AssignmentResource` |
| PUT | `/assignments/{assignment}` | ✓ | Instructor | Course-owner | `AssignmentController@update` | `UpdateAssignmentRequest` | `AssignmentPolicy@update` | `AssignmentService` | `AssignmentResource` |
| DELETE | `/assignments/{assignment}` | ✓ | Instructor | Course-owner | `AssignmentController@destroy` | — | `AssignmentPolicy@delete` | `AssignmentService` | — |

### Endpoint Details

### `GET /courses/{course}/assignments`

**Purpose:** assignments of a published course (enrolled students + owner/Admin).

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Enrolled or Course-owner

**Controller:** `AssignmentController@index`
**Form Request:** —
**Policy:** `AssignmentPolicy@viewAny`
**Service:** —
**Resource:** `AssignmentResource`

**Request body / Parameters (query):** `sort`, `page`, `per_page`.

**Business Rules:**
- detail includes the student's own submission summary for students.

**Response:** `200` — paginated `AssignmentResource` list.

### `GET /assignments/{assignment}`

**Purpose:** view a single assignment.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Enrolled or Course-owner

**Controller:** `AssignmentController@show`
**Form Request:** —
**Policy:** `AssignmentPolicy@view`
**Service:** —
**Resource:** `AssignmentResource`

**Business Rules:**
- detail includes the student's own submission summary for students.

**Response:** `200` — `AssignmentResource`.

### `POST /courses/{course}/assignments`

**Purpose:** create an assignment in a course (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `AssignmentController@store`
**Form Request:** `StoreAssignmentRequest`
**Policy:** `AssignmentPolicy@create`
**Service:** `AssignmentService`
**Resource:** `AssignmentResource`

**Request body / Parameters:** `title`, `description`, `due_date` (required), `total_marks` (required int).

**Validation:** required fields; `due_date` required; `total_marks` required integer.

**Response:** `201` — `AssignmentResource`.

### `PUT /assignments/{assignment}`

**Purpose:** update an assignment (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `AssignmentController@update`
**Form Request:** `UpdateAssignmentRequest`
**Policy:** `AssignmentPolicy@update`
**Service:** `AssignmentService`
**Resource:** `AssignmentResource`

**Request body / Parameters:** same as store (`title`, `description`, `due_date`, `total_marks`).

**Undecided:** resubmission (PRD: "once unless resubmission is allowed") has no schema flag → NEEDS DECISION (Assignments).

**Response:** `200` — `AssignmentResource`.

### `DELETE /assignments/{assignment}`

**Purpose:** delete an assignment (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `AssignmentController@destroy`
**Form Request:** —
**Policy:** `AssignmentPolicy@delete`
**Service:** `AssignmentService`
**Resource:** —

**Edge Cases:**
- deleting an assignment with submissions cascades (current behavior) — decide block vs. cascade → NEEDS DECISION (Assignments).

**Response:** `204`.

---

## 8. Submissions (4 endpoints)

Student submissions and instructor grading.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/assignments/{assignment}/submissions` | ✓ | Instructor | Course-owner | `SubmissionController@assignmentIndex` | — | `SubmissionPolicy@viewAny` | — | `SubmissionResource` |
| GET | `/submissions/{submission}` | ✓ | Student/Instructor | Self or Course-owner | `SubmissionController@show` | — | `SubmissionPolicy@view` | — | `SubmissionResource` |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| POST | `/assignments/{assignment}/submit` | ✓ | Student | Self | `SubmissionController@store` | `SubmitAssignmentRequest` | `SubmissionPolicy@create` | `SubmissionService` | `SubmissionResource` |
| PUT | `/submissions/{submission}/grade` | ✓ | Instructor | Course-owner | `SubmissionController@grade` | `GradeSubmissionRequest` | `SubmissionPolicy@grade` | `SubmissionService` | `SubmissionResource` |

### Endpoint Details

### `POST /assignments/{assignment}/submit`

**Purpose:** student submits an assignment.

**Authentication:** Required
**Roles:** Student
**Ownership:** Self

**Controller:** `SubmissionController@store`
**Form Request:** `SubmitAssignmentRequest`
**Policy:** `SubmissionPolicy@create`
**Service:** `SubmissionService`
**Resource:** `SubmissionResource`

**Request body / Parameters:** `submission_file` required; `grade` is **never** accepted here.

**Business Rules:**
- verified email; enrolled;
- one submission per student per assignment (resubmission not modeled → NEEDS DECISION);
- `submission_at` server-set.

**Edge Cases:**
- submission after `due_date` must be **flagged late** — no `is_late` column; derive `is_late = submission_at > due_date` in the resource → NEEDS DECISION (Submissions).

**Errors:** `409` already submitted; `422` locked / not permitted.

**Response:** `201` — `SubmissionResource`.

### `GET /assignments/{assignment}/submissions`

**Purpose:** instructor grading queue.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `SubmissionController@assignmentIndex`
**Form Request:** —
**Policy:** `SubmissionPolicy@viewAny`
**Service:** —
**Resource:** `SubmissionResource`

**Request body / Parameters (query):** `page`, `is_late`/`status` filter, `sort`.

**Response:** `200` — paginated `SubmissionResource` list.

### `GET /submissions/{submission}`

**Purpose:** view a single submission.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Self or Course-owner

**Controller:** `SubmissionController@show`
**Form Request:** —
**Policy:** `SubmissionPolicy@view`
**Service:** —
**Resource:** `SubmissionResource`

**Business Rules:**
- student sees own submission + grade;
- course instructor/Admin sees any submission in the course.

**Response:** `200` — `SubmissionResource`.

### `PUT /submissions/{submission}/grade`

**Purpose:** instructor grades a submission.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `SubmissionController@grade`
**Form Request:** `GradeSubmissionRequest`
**Policy:** `SubmissionPolicy@grade`
**Service:** `SubmissionService`
**Resource:** `SubmissionResource`

**Request body / Parameters:** `grade` (int, `0..assignment.total_marks`), optional feedback.

**Validation:** grade within `0..assignment.total_marks`.

**Schema conflict:** `submissions.grade` is non-nullable, but submissions exist *before* grading — make nullable or adopt a sentinel → NEEDS DECISION (Submissions).

**Business Rules:**
- enqueue a "graded" notification (PRD trigger).

**Response:** `200` — `SubmissionResource`.---

## 9. Quizzes (5 endpoints)

Quizzes live in the `quizes` table (typo — see NEEDS DECISION). Windows are `start_at`/`end_at`.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/courses/{course}/quizzes` | ✓ | Student/Instructor | Enrolled or Course-owner | `QuizController@index` | — | `QuizPolicy@viewAny` | — | `QuizResource` |
| GET | `/quizzes/{quiz}` | ✓ | Student/Instructor | Enrolled or Course-owner | `QuizController@show` | — | `QuizPolicy@view` | — | `QuizResource` |
| POST | `/courses/{course}/quizzes` | ✓ | Instructor | Course-owner | `QuizController@store` | `StoreQuizRequest` | `QuizPolicy@create` | `QuizService` | `QuizResource` |
| PUT | `/quizzes/{quiz}` | ✓ | Instructor | Course-owner | `QuizController@update` | `UpdateQuizRequest` | `QuizPolicy@update` | `QuizService` | `QuizResource` |
| DELETE | `/quizzes/{quiz}` | ✓ | Instructor | Course-owner | `QuizController@destroy` | — | `QuizPolicy@delete` | `QuizService` | — |

### Endpoint Details

### `GET /courses/{course}/quizzes`

**Purpose:** quiz list for a course.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Enrolled or Course-owner

**Controller:** `QuizController@index`
**Form Request:** —
**Policy:** `QuizPolicy@viewAny`
**Service:** —
**Resource:** `QuizResource`

**Business Rules:**
- students see only quizzes within their window; owner/Admin see all.

**Edge Cases:**
- a quiz on a non-published course is hidden from students.

**Response:** `200` — paginated `QuizResource` list.

### `GET /quizzes/{quiz}`

**Purpose:** quiz detail.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Enrolled or Course-owner

**Controller:** `QuizController@show`
**Form Request:** —
**Policy:** `QuizPolicy@view`
**Service:** —
**Resource:** `QuizResource`

**Business Rules:**
- students see only quizzes within their window; owner/Admin see all.

**Edge Cases:**
- a quiz on a non-published course is hidden from students.

**Response:** `200` — `QuizResource`.

### `POST /courses/{course}/quizzes`

**Purpose:** create a quiz in a course (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `QuizController@store`
**Form Request:** `StoreQuizRequest`
**Policy:** `QuizPolicy@create`
**Service:** `QuizService`
**Resource:** `QuizResource`

**Request body / Parameters:** `title`, optional `description`, optional `start_at`/`end_at` (the open window), `total_marks` (required).

**Validation:** `total_marks` required.

**Response:** `201` — `QuizResource`.

### `PUT /quizzes/{quiz}`

**Purpose:** update a quiz (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `QuizController@update`
**Form Request:** `UpdateQuizRequest`
**Policy:** `QuizPolicy@update`
**Service:** `QuizService`
**Resource:** `QuizResource`

**Request body / Parameters:** window, marks, title.

**Undecided:** PRD "max attempts is configurable" — no `max_attempts` column → NEEDS DECISION (Quizzes/Attempts).

**Response:** `200` — `QuizResource`.

### `DELETE /quizzes/{quiz}`

**Purpose:** delete a quiz (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `QuizController@destroy`
**Form Request:** —
**Policy:** `QuizPolicy@delete`
**Service:** `QuizService`
**Resource:** —

**Edge Cases:**
- deletion cascades attempts/questions (current behavior) — decide → NEEDS DECISION.

**Response:** `204`.

---

## 10. Questions (4 endpoints)

Questions belong to one quiz and carry a mark. **Auto-scoring requires stored options/answers — the
schema has none** → NEEDS DECISION (Questions). All 4 endpoints are CRUD.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/quizzes/{quiz}/questions` | ✓ | Instructor | Course-owner | `QuestionController@index` | — | `QuestionPolicy@viewAny` | — | `QuestionResource` |
| POST | `/quizzes/{quiz}/questions` | ✓ | Instructor | Course-owner | `QuestionController@store` | `StoreQuestionRequest` | `QuestionPolicy@create` | `QuestionService` | `QuestionResource` |
| PUT | `/questions/{question}` | ✓ | Instructor | Course-owner | `QuestionController@update` | `UpdateQuestionRequest` | `QuestionPolicy@update` | `QuestionService` | `QuestionResource` |
| DELETE | `/questions/{question}` | ✓ | Instructor | Course-owner | `QuestionController@destroy` | — | `QuestionPolicy@delete` | `QuestionService` | — |

### Endpoint Details

### `GET /quizzes/{quiz}/questions`

**Purpose:** full question list for the course owner/Admin.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `QuestionController@index`
**Form Request:** —
**Policy:** `QuestionPolicy@viewAny`
**Service:** —
**Resource:** `QuestionResource`

**Business Rules:**
- **never** exposes answer keys to students (student-facing questions are surfaced only inside an attempt flow).

**Response:** `200` — paginated `QuestionResource` list.

### `POST /quizzes/{quiz}/questions`

**Purpose:** add a question to a quiz (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `QuestionController@store`
**Form Request:** `StoreQuestionRequest`
**Policy:** `QuestionPolicy@create`
**Service:** `QuestionService`
**Resource:** `QuestionResource`

**Request body / Parameters:** `question_text` (required), `question_type` (default `mcq`), `mark` (required int).

**Undecided:** correct answer + options cannot be stored yet → NEEDS DECISION (Questions).

**Response:** `201` — `QuestionResource`.

### `PUT /questions/{question}`

**Purpose:** update a question (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `QuestionController@update`
**Form Request:** `UpdateQuestionRequest`
**Policy:** `QuestionPolicy@update`
**Service:** `QuestionService`
**Resource:** `QuestionResource`

**Request body / Parameters:** text/type/mark (+ answer fields once they exist).

**Response:** `200` — `QuestionResource`.

### `DELETE /questions/{question}`

**Purpose:** delete a question (owner/Admin).

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `QuestionController@destroy`
**Form Request:** —
**Policy:** `QuestionPolicy@delete`
**Service:** `QuestionService`
**Resource:** —

**Business Rules:**
- recompute the quiz's effective total marks afterwards.

**Response:** `204`.

---

## 11. Attempts (4 endpoints)

Recorded quiz attempts with automatic-scoring intent.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/users/me/attempts` | ✓ | Student | Self | `AttemptController@myIndex` | — | `AttemptPolicy@viewOwn` | — | `AttemptResource` |
| GET | `/attempts/{attempt}` | ✓ | Student/Instructor | Self or Course-owner | `AttemptController@show` | — | `AttemptPolicy@view` | — | `AttemptResource` |
| GET | `/quizzes/{quiz}/attempts` | ✓ | Instructor | Course-owner | `AttemptController@quizIndex` | — | `AttemptPolicy@viewQuiz` | — | `AttemptResource` |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| POST | `/quizzes/{quiz}/attempts` | ✓ | Student | Self | `AttemptController@store` | `StartAttemptRequest` | `AttemptPolicy@create` | `AttemptService` | `AttemptResource` |

### Endpoint Details

### `POST /quizzes/{quiz}/attempts`

**Purpose:** start (and complete) a quiz attempt with automatic scoring.

**Authentication:** Required
**Roles:** Student
**Ownership:** Self

**Controller:** `AttemptController@store`
**Form Request:** `StartAttemptRequest`
**Policy:** `AttemptPolicy@create`
**Service:** `AttemptService`
**Resource:** `AttemptResource`

**Request body / Parameters:** `answers` (map of `question_id → answer`).

**Business Rules:**
- verified email; enrolled;
- window open (`start_at <= now <= end_at`);
- within max attempts (**`max_attempts` not stored**);
- scoring relies on stored answers (**none exist**) → NEEDS DECISION.

**Edge Cases:**
- attempt before `start_at` or after `end_at` → `409`/`422`.

**Schema quirks:** `attempts.status` enum is `successed|failed` (typo); `score` is non-nullable though an in-progress attempt has no score → NEEDS DECISION (Attempts).

**Response:** `201` — `AttemptResource` with computed `score`, `status`, `started_at`, `completed_at`.

### `GET /users/me/attempts`

**Purpose:** the student's attempt history.

**Authentication:** Required
**Roles:** Student
**Ownership:** Self

**Controller:** `AttemptController@myIndex`
**Form Request:** —
**Policy:** `AttemptPolicy@viewOwn`
**Service:** —
**Resource:** `AttemptResource`

**Business Rules:**
- myIndex: attempt history (paginated).

**Response:** `200` — paginated `AttemptResource` list.

### `GET /attempts/{attempt}`

**Purpose:** view a single attempt.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Self or Course-owner

**Controller:** `AttemptController@show`
**Form Request:** —
**Policy:** `AttemptPolicy@view`
**Service:** —
**Resource:** `AttemptResource`

**Business Rules:**
- owning student sees their result;
- course instructor/Admin see all attempts on their quiz.

**Response:** `200` — `AttemptResource`.

### `GET /quizzes/{quiz}/attempts`

**Purpose:** instructor aggregate view for a quiz.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `AttemptController@quizIndex`
**Form Request:** —
**Policy:** `AttemptPolicy@viewQuiz`
**Service:** —
**Resource:** `AttemptResource`

**Request body / Parameters (query):** `page`, `sort`, `status`.

**Business Rules:**
- quizIndex: instructor aggregates (score distribution).

**Response:** `200` — paginated `AttemptResource` list.

---

## 12. Progress (4 endpoints)

Per-enrollment progress. The completion/percentage reconciliation formula is undefined in the schema →
NEEDS DECISION (Progress).

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/users/me/progress` | ✓ | Student | Self | `ProgressController@myIndex` | — | `ProgressPolicy@viewOwn` | `ProgressService` | `ProgressResource` |
| GET | `/enrollments/{enrollment}/progress` | ✓ | Student/Instructor | Self or Course-owner | `ProgressController@enrollmentShow` | — | `ProgressPolicy@view` | `ProgressService` | `ProgressResource` |
| GET | `/courses/{course}/progress` | ✓ | Instructor | Course-owner | `ProgressController@courseIndex` | — | `ProgressPolicy@viewCourse` | `ProgressService` | `ProgressResource` |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| POST | `/lessons/{lesson}/complete` | ✓ | Student | Self | `ProgressController@completeLesson` | — | `ProgressPolicy@create` | `ProgressService` | `ProgressResource` |

### Endpoint Details

### `GET /users/me/progress`

**Purpose:** progress summary per enrollment for the current student.

**Authentication:** Required
**Roles:** Student
**Ownership:** Self

**Controller:** `ProgressController@myIndex`
**Form Request:** —
**Policy:** `ProgressPolicy@viewOwn`
**Service:** `ProgressService`
**Resource:** `ProgressResource`

**Business Rules:**
- progress summary per enrollment (course, % complete, completed lessons);
- progress is per-enrollment — never global across courses.

**Response:** `200` — `ProgressResource`.

### `GET /enrollments/{enrollment}/progress`

**Purpose:** all lessons + computed % for one enrollment.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Self or Course-owner

**Controller:** `ProgressController@enrollmentShow`
**Form Request:** —
**Policy:** `ProgressPolicy@view`
**Service:** `ProgressService`
**Resource:** `ProgressResource`

**Business Rules:**
- owning student, course instructor, or Admin.

**Response:** `200` — `ProgressResource`.

### `GET /courses/{course}/progress`

**Purpose:** instructor aggregate view of all students' progress.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `ProgressController@courseIndex`
**Form Request:** —
**Policy:** `ProgressPolicy@viewCourse`
**Service:** `ProgressService`
**Resource:** `ProgressResource`

**Business Rules:**
- paginated; owner/Admin only.

**Response:** `200` — paginated `ProgressResource` list.

### `POST /lessons/{lesson}/complete`

**Purpose:** mark a lesson complete — creates a `progresses` row (`lesson_id`, `enrollment_id`, `completed_at`).

**Authentication:** Required
**Roles:** Student
**Ownership:** Self

**Controller:** `ProgressController@completeLesson`
**Form Request:** —
**Policy:** `ProgressPolicy@create`
**Service:** `ProgressService`
**Resource:** `ProgressResource`

**Business Rules:**
- verified email;
- enrolled in the lesson's course;
- idempotent on repeat (`200` + existing row).

**Edge Cases:**
- completing a lesson in a draft course → forbid unless course owner.

**Undecided:** reconciliation with assignments/quizzes + automatic percentage → NEEDS DECISION (Progress).

**Response:** `200` — `ProgressResource`.---

## 13. Certificates (4 endpoints)

Issued per completed course. The issue trigger is undefined → NEEDS DECISION (Certificates).

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/users/me/certificates` | ✓ | Student | Self | `CertificateController@myIndex` | — | `CertificatePolicy@viewOwn` | — | `CertificateResource` |
| GET | `/certificates/{certificate}` | ✓ | Student/Admin | Self or Admin | `CertificateController@show` | — | `CertificatePolicy@view` | — | `CertificateResource` |
| GET | `/courses/{course}/certificates` | ✓ | Instructor | Course-owner | `CertificateController@courseIndex` | — | `CertificatePolicy@viewCourse` | — | `CertificateResource` |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| POST | `/enrollments/{enrollment}/certificate` | ✓ | Student/Instructor | Self or Course-owner | `CertificateController@issue` | — | `CertificatePolicy@issue` | `CertificateService` | `CertificateResource` |

### Endpoint Details

### `GET /users/me/certificates`

**Purpose:** the student's own certificates.

**Authentication:** Required
**Roles:** Student
**Ownership:** Self

**Controller:** `CertificateController@myIndex`
**Form Request:** —
**Policy:** `CertificatePolicy@viewOwn`
**Service:** —
**Resource:** `CertificateResource`

**Business Rules:**
- myIndex: own certificates (paginated).

**Response:** `200` — paginated `CertificateResource` list.

### `GET /certificates/{certificate}`

**Purpose:** view a single certificate.

**Authentication:** Required
**Roles:** Student/Admin
**Ownership:** Self or Admin

**Controller:** `CertificateController@show`
**Form Request:** —
**Policy:** `CertificatePolicy@view`
**Service:** —
**Resource:** `CertificateResource`

**Business Rules:**
- the named student or Admin (instructors use the course listing).

**Response:** `200` — `CertificateResource`.

### `GET /courses/{course}/certificates`

**Purpose:** certificates issued in a course.

**Authentication:** Required
**Roles:** Instructor
**Ownership:** Course-owner

**Controller:** `CertificateController@courseIndex`
**Form Request:** —
**Policy:** `CertificatePolicy@viewCourse`
**Service:** —
**Resource:** `CertificateResource`

**Business Rules:**
- course owner/Admin.

**Response:** `200` — paginated `CertificateResource` list.

### `POST /enrollments/{enrollment}/certificate`

**Purpose:** generate a certificate on completion.

**Authentication:** Required
**Roles:** Student/Instructor
**Ownership:** Self or Course-owner

**Controller:** `CertificateController@issue`
**Form Request:** —
**Policy:** `CertificatePolicy@issue`
**Service:** `CertificateService`
**Resource:** `CertificateResource`

**Business Rules:**
- the service derives `issued_at` and `certificate_number`.

**Undecided:** automatic (on 100% completion) vs. manual issuance, who may trigger it, and uniqueness of `certificate_number` → NEEDS DECISION (Certificates).

**Response:** `201` — `CertificateResource`.

---

## 14. Notifications (4 endpoints)

Read/unread notification inbox.

**Structural risk:** the `User` model uses Laravel's `Notifiable` trait, whose default table is
`notifications` (polymorphic). This project's custom `notifications` migration uses a scalar `user_id` —
the two collide on the same table name → NEEDS DECISION (Notifications). PRD requires queued delivery on
defined triggers (enrollment, grading, deadlines); the queue wiring is `QUEUE_CONNECTION=database`.

### CRUD Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/users/me/notifications` | ✓ | all | Self | `NotificationController@myIndex` | — | `NotificationPolicy@viewOwn` | — | `NotificationResource` |
| GET | `/notifications/{notification}` | ✓ | all | Self | `NotificationController@show` | — | `NotificationPolicy@view` | — | `NotificationResource` |

### Business / Action Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| PUT | `/notifications/{notification}/read` | ✓ | all | Self | `NotificationController@markRead` | — | `NotificationPolicy@update` | `NotificationService` | `NotificationResource` |
| PUT | `/notifications/read-all` | ✓ | all | Self | `NotificationController@markAllRead` | — | `NotificationPolicy@update` | `NotificationService` | `NotificationResource` |

### Endpoint Details

### `GET /users/me/notifications`

**Purpose:** the user's notification inbox.

**Authentication:** Required
**Roles:** all
**Ownership:** Self

**Controller:** `NotificationController@myIndex`
**Form Request:** —
**Policy:** `NotificationPolicy@viewOwn`
**Service:** —
**Resource:** `NotificationResource`

**Request body / Parameters (query):** `unread`, `type`, `page`, `per_page`.

**Response:** `200` — paginated `NotificationResource` list.

### `GET /notifications/{notification}`

**Purpose:** view a single notification.

**Authentication:** Required
**Roles:** all
**Ownership:** Self

**Controller:** `NotificationController@show`
**Form Request:** —
**Policy:** `NotificationPolicy@view`
**Service:** —
**Resource:** `NotificationResource`

**Business Rules:**
- recipient only.

**Response:** `200` — `NotificationResource`.

### `PUT /notifications/{notification}/read`

**Purpose:** mark one notification as read.

**Authentication:** Required
**Roles:** all
**Ownership:** Self

**Controller:** `NotificationController@markRead`
**Form Request:** —
**Policy:** `NotificationPolicy@update`
**Service:** `NotificationService`
**Resource:** `NotificationResource`

**Business Rules:**
- sets `is_read = true`.

**Response:** `200`.

### `PUT /notifications/read-all`

**Purpose:** mark all notifications as read.

**Authentication:** Required
**Roles:** all
**Ownership:** Self

**Controller:** `NotificationController@markAllRead`
**Form Request:** —
**Policy:** `NotificationPolicy@update`
**Service:** `NotificationService`
**Resource:** `NotificationResource`

**Business Rules:**
- marks all notifications as read for the current user.

**Response:** `200`.

---

## 15. Reports (3 endpoints)

Read-only aggregation — no table/model needed, the module is a query layer. All 3 are query endpoints.

### Query Endpoints

| Method | Endpoint | Auth | Roles | Ownership | Controller | Request | Policy | Service | Resource |
| ------ | -------- | ---- | ----- | --------- | ---------- | ------- | ------ | ------- | -------- |
| GET | `/reports/courses` | ✓ | Instructor/Admin | Course-owner (instructor) | `ReportController@courseIndex` | `ReportRequest` | `ReportPolicy@viewAny` | `ReportService` | `ReportResource` |
| GET | `/reports/courses/{course}` | ✓ | Instructor/Admin | Course-owner | `ReportController@courseShow` | `ReportRequest` | `ReportPolicy@view` | `ReportService` | `ReportResource` |
| GET | `/reports/courses/{course}/students` | ✓ | Instructor/Admin | Course-owner | `ReportController@students` | `ReportRequest` | `ReportPolicy@view` | `ReportService` | `ReportResource` |

### Endpoint Details

### `GET /reports/courses`

**Purpose:** per-course summary.

**Authentication:** Required
**Roles:** Instructor/Admin
**Ownership:** Course-owner (instructor)

**Controller:** `ReportController@courseIndex`
**Form Request:** `ReportRequest`
**Policy:** `ReportPolicy@viewAny`
**Service:** `ReportService`
**Resource:** `ReportResource`

**Request body / Parameters (query):** `q`, `status`, `from`, `to`, `sort`, `page`, `per_page`.

**Business Rules:**
- instructors see **only their own courses**; Admins see platform-wide;
- metrics: enrollment count, completion rate, avg assignment grade, avg quiz score (definitions depend on Progress/Attempts decisions).

**Response:** `200` — paginated `ReportResource` list.

### `GET /reports/courses/{course}`

**Purpose:** detailed course report.

**Authentication:** Required
**Roles:** Instructor/Admin
**Ownership:** Course-owner

**Controller:** `ReportController@courseShow`
**Form Request:** `ReportRequest`
**Policy:** `ReportPolicy@view`
**Service:** `ReportService`
**Resource:** `ReportResource`

**Business Rules:**
- detailed course report (enrollments, completion trend, grade ranges, quiz score distribution);
- owner/Admin only.

**Response:** `200` — `ReportResource`.

### `GET /reports/courses/{course}/students`

**Purpose:** per-student breakdown for a course.

**Authentication:** Required
**Roles:** Instructor/Admin
**Ownership:** Course-owner

**Controller:** `ReportController@students`
**Form Request:** `ReportRequest`
**Policy:** `ReportPolicy@view`
**Service:** `ReportService`
**Resource:** `ReportResource`

**Request body / Parameters (query):** `page`, `sort`.

**Business Rules:**
- per-student breakdown (progress %, grades, attempts).

**Response:** `200` — paginated `ReportResource` list.---

## 16. Edge cases (PRD §8) — handling strategy

| Edge case | Where | Handling |
| --- | --- | --- |
| Student enrolls twice in same course | Enrollments | Unique composite index `(user_id, course_id)` → `409`. **NEEDS DECISION: constraint not present.** |
| Instructor deletes course with active students | Courses | Decide: block (`409`) / archive-only / soft delete. Schema currently cascades. **NEEDS DECISION.** |
| Assignment submitted after deadline | Submissions | Compute derived `is_late = submission_at > due_date`; flag in resource. **NEEDS DECISION: no `is_late` column.** |
| Quiz attempt after window closed | Attempts | Reject with `409`/`422` when `now > end_at` (or before `start_at`). |
| Instructor removes a completed lesson | Lessons/Progress | `progresses.lesson_id` cascades → record loss. Decide: block / soft-delete / preserve. **NEEDS DECISION.** |
| Concurrent enrollment requests | Enrollments | Rely on DB unique constraint + retry/transaction; **NEEDS DECISION: constraint not present.** |
| Duplicate course title per instructor | Courses | Service validation + **unique index `(user_id, title)`**; **NEEDS DECISION: no constraint.** |
| Lesson published before parent course | Lessons | Course-level publish is the single gate; no lesson-level publish flag exists. |

---

## 17. Non-functional requirements mapping

| Requirement | API design |
| --- | --- |
| Auth (+ OWASP) | JWT bearer; email verification gate; role + ownership checks via Policies; never trust client ids. |
| Authorization | Policies/Gates for every authenticated domain endpoint; ownership distinct from role. |
| Validation | Form Requests on every mutating endpoint; consistent `422` error shape. |
| Audit logs | Write audit entries for role changes, grading, publishing, archiving (via services). |
| Pagination | All collections paginated; `per_page` capped; `meta` block included. |
| Search/filter/sort | Query params on catalog/roster/report endpoints (`q`, `status`, `category_id`, `from`, `to`, `sort`). |
| Queued notifications | Notifications/emails queued (jobs), never synchronous; `QUEUE_CONNECTION=database`. |
| Caching | Safe caching only for public catalogs (courses/categories); bust on publish/update. |
| Structured logging | Log auth events, state changes, grading with context (user, resource, ip). |
| Consistent error handling | Uniform envelope: `message`, `errors`, `code`; exceptions mapped in `bootstrap/app.php` (JSON for `/api/*`). |
| Reliability | Transactions around multi-row mutations (enroll, attempt, progress, certificate). |
| Maintainability | Services for business logic, repositories/queries for aggregation, thin controllers. |
| API docs | This file is the living spec; OpenAPI export later. |
| Scalability | Stateless JWT, queue workers, pagination, index-friendly queries. |

---

## 18. Role Permission Matrix

Legend: `✓` allowed · `✗` not allowed · `◇` ownership-dependent · `ADMIN` admin-only
(`Guest` = unauthenticated; `Admin` implicitly allowed on every owned resource.)

| Action | Guest | Student | Instructor | Admin |
| --- | --- | --- | --- | --- |
| Register / login / password reset | ✓ | — | — | — |
| Logout / refresh / email verify / resend | ✗ | ✓ | ✓ | ✓ |
| View profile (me) | ✗ | ✓ | ✓ | ✓ |
| Update own profile | ✗ | ✓ | ✓ | ✓ |
| List / view users | ✗ | ✗ | ✗ | ADMIN |
| Change user role | ✗ | ✗ | ✗ | ADMIN |
| Browse categories | ✓ | ✓ | ✓ | ✓ |
| Create / edit / delete categories | ✗ | ✗ | ✗ | ADMIN |
| Browse published courses | ✓ | ✓ | ✓ | ✓ |
| Create course | ✗ | ✗ | ✓ | ✓ |
| Edit / archive / publish own course | ✗ | ✗ | ◇ | ✓ |
| Delete course | ✗ | ✗ | ◇ | ✓ |
| View lessons (course) | ✗ | ◇ enrolled | ◇ owner | ✓ |
| Create / edit / delete lessons | ✗ | ✗ | ◇ | ✓ |
| Reorder lessons | ✗ | ✗ | ◇ | ✓ |
| Enroll in course | ✗ | ✓ verified | ✗ | ✗ |
| View enrollments (course roster) | ✗ | ✗ | ◇ | ✓ |
| View assignments of course | ✗ | ◇ enrolled | ◇ | ✓ |
| Create / edit / delete assignments | ✗ | ✗ | ◇ | ✓ |
| Submit assignment | ✗ | ✓ verified | ✗ | ✗ |
| View / grade submissions | ✗ | ◇ own | ◇ | ✓ |
| View quizzes of course | ✗ | ◇ enrolled | ◇ | ✓ |
| Create / edit / delete quizzes | ✗ | ✗ | ◇ | ✓ |
| Manage quiz questions | ✗ | ✗ | ◇ | ✓ |
| Start quiz attempt | ✗ | ✓ verified | ✗ | ✗ |
| View attempts | ✗ | ◇ own | ◇ | ✓ |
| View own progress | ✗ | ✓ | ✗ | ✓ |
| View course progress aggregate | ✗ | ✗ | ◇ | ✓ |
| Complete a lesson | ✗ | ✓ verified | ✗ | ✗ |
| View / generate certificates | ✗ | ◇ own | ◇ course | ✓ |
| View notifications | ✗ | ✓ own | ✓ own | ✓ own |
| Run reports | ✗ | ✗ | ◇ own | ✓ platform-wide |

---

## 19. Endpoint Summary

All figures below are computed from the module tables above and must be re-validated whenever endpoints change.

### By module

| Module | CRUD/query | Action | Total |
| --- | --- | --- | --- |
| 1. Authentication | 0 | 8 | 8 |
| 2. Users | 5 | 1 | 6 |
| 3. Categories | 5 | 0 | 5 |
| 4. Courses | 5 | 3 | 8 |
| 5. Lessons | 5 | 1 | 6 |
| 6. Enrollments | 3 | 1 | 4 |
| 7. Assignments | 5 | 0 | 5 |
| 8. Submissions | 2 | 2 | 4 |
| 9. Quizzes | 5 | 0 | 5 |
| 10. Questions | 4 | 0 | 4 |
| 11. Attempts | 3 | 1 | 4 |
| 12. Progress | 3 | 1 | 4 |
| 13. Certificates | 3 | 1 | 4 |
| 14. Notifications | 2 | 2 | 4 |
| 15. Reports | 3 | 0 | 3 |
| **Total** | **53** | **21** | **74** |

### By HTTP method

| Method | Count |
| --- | --- |
| GET | 33 |
| POST | 22 |
| PUT | 13 |
| DELETE | 6 |
| **Total** | **74** |

### By role reachability (count of endpoints the role may call, with at least owner-scope access)

| Role | Count |
| --- | --- |
| Guest (public) | 8 |
| Student | 38 |
| Instructor | 51 |
| Admin | 74 |

### Cross-cutting counts

| Measure | Count |
| --- | --- |
| Endpoints requiring authentication (74 − 8 public) | 66 |
| Public endpoints (register, login, forgot, reset, categories ×2, courses ×2) | 8 |
| Endpoints requiring ownership checks (self-resource or course-owner) | 52 |
| Endpoints requiring Policies/Gates (all authenticated endpoints) | 66 |
| Endpoints requiring Services (business-logic-bearing) | 43 |
| Business / action endpoints | 21 |
| CRUD & query endpoints (74 − 21) | 53 |
| Endpoints whose design depends on a **NEEDS DECISION** | 34 |

Action endpoints (21): register, login, logout, refresh, verify, resend, forgot, reset,
`PUT /users/{user}/role`, publish, archive, unarchive, lessons reorder, enroll, submit,
grade, start attempt, complete lesson, issue certificate, mark notification read, read-all.

Endpoints depending on a NEEDS DECISION (34): courses index/show/create/update/delete/publish/archive/unarchive (8),
enroll (1), update assignment + submit (2), submit + grade (2), update quiz (1), questions ×4,
attempts ×4, progress ×4, issue certificate (1), notifications ×4, reports ×3.

---

## 20. Implementation Order

Rationale: build bottom-up in dependency order so each phase can be verified with tests before the
next depends on it.

1. **Auth & users** (`auth`, users CRUD) — nothing works without identity; email verification gates
   everything downstream. Also wire the JWT `api` guard + register `routes/api.php` (currently not
   loaded by `bootstrap/app.php`).
2. **Roles & categories** — needed by course creation; role-change endpoint defines the authorization
   model used everywhere.
3. **Courses + lifecycle (publish/archive)** — the central aggregate; publishes the draft/published/archived
   rules the rest of the domain depends on. Resolve the `courses.status` conflict first.
4. **Lessons + reorder** — content depends on courses; reorder design validated against progress safety.
5. **Enrollments + progress (lesson completion)** — enrollments are required before students can touch
   any content; progress mechanics (idempotent completion, per-enrollment percentage) are designed here.
6. **Assignments + submissions + grading** — depend on courses/enrollments; creates the notification
   trigger for grading.
7. **Quizzes + questions + attempts** — the most decision-heavy module (answers, scoring, max attempts);
   needs answers + attempt schema decisions; depends on courses/enrollments.
8. **Certificates** — depends on completion (progress) definition.
9. **Notifications** — depends on triggers from enrollment, grading, deadlines; the inbox endpoints are
   trivial once targets exist. Resolve the `Notifiable`-table collision first.
10. **Reports** — read-model aggregation over every module above; last by design.

Why this order: identity → taxonomy → owned aggregates (courses) → content (lessons) → participation
(enrollments/progress) → assessment (assignments/quizzes → submissions/attempts) → lifecycle artifacts
(certificates) → communication (notifications) → insights (reports). Each phase is independently testable.

---

## 21. Project Findings

1. **What exists:** a Laravel 13 (PHP ^8.3) skeleton with **models + migrations only**. There are no
   controllers (only abstract `Controller.php`), no API routes (`routes/api.php` exists but is empty and
   is **not registered** in `bootstrap/app.php`), no form requests, resources, policies, services, or feature tests.
2. **Entities/models:** `users`, `roles`, `categories`, `courses`, `lessons`, `enrollments`, `progresses`,
   `assignments`, `submissions`, `quizes`, `questions`, `attempts`, `certificates`, `notifications`
   (+ default `password_reset_tokens`, `sessions`, `cache`, `jobs`). All 14 domain models exist.
3. **Relationships:** `User` ↔ `Role` (belongsTo/hasMany), `User`/`Category`/`Course` (hasMany/belongsTo),
   `Course` → lessons/assignments/`quizes`/certificates/enrollments; `Lesson` → `progresses` (lesson
   completions); `Enrollment` → `progresses`; `Quiz` → `questions`/`attempts`; `Assignment` → `submissions`;
   `Certificate`/`Submission`/`Attempt`/`Progress`/`Notification` belong to `User`.
4. **What BRD/PRD requires:** the 9 in-scope modules (auth+roles, courses, lessons, assignments, quizzes,
   progress, email notifications, reporting) plus enrollment, certificates; out-of-scope: video, SCORM/xAPI,
   payments, live streaming, mobile, AI tutor.
5. **Where the implementation matches:** the schema covers every required module end-to-end (auth fields,
   enrollment, assignments/submissions, quiz/attempts, progress rows, certificates, notifications,
   categories/roles for taxonomy and RBAC). The `quizes`/`questions`/`attempts` chain, enrollment, and
   lesson-progress chains are all modeled.
6. **Where the implementation does not yet support the requirements:** the course lifecycle status
   (`draft/published/archived`) is missing (the `courses.status` enum holds progress states instead); quiz
   answers/options and auto-scoring data are missing; `max_attempts` is missing; resubmission flag missing;
   `submissions.grade` is non-nullable (blocks "created, graded later"); an `is_late` flag missing
   (derivable); enrollment and course-title uniqueness constraints missing; certificate issuance trigger
   unspecified; the custom `notifications` table collides with Laravel's `Notifiable` table; the `quizes`
   table name typo means the `Quiz` model (`quizzes` default table) cannot query today; the `attempts.status`
   enum typo `successed`; no API layer, no auth guard, no route registration at all.

---

## 22. Assumptions

- "Guest" is the unauthenticated state; the DB `roles` enum has only `Admin|Student|Instructor`.
- A user may self-register with either `Student` or `Instructor`; `Admin` is created by seeding/Admin
  assignment. (Alternative design — self-registration is Student-only — is a product call; see NEEDS DECISION.)
- Categories are managed by Admin (platform taxonomy); instructors only reference them.
- Email verification is delivered through the existing notification/queued-email stack
  (`MAIL_MAILER=log` in dev).
- Reporting is a read/aggregate module — no `reports` table is required.
- Response envelope and pagination conventions in §0 are proposed; exact shapes are open to refinement.
- The misspelled `docs/buisness_requirments.html` is the BRD/PRD referenced by the task as
  `docs/business-requirements.html`.

---

## 23. NEEDS DECISION

Every bullet blocks finalizing one or more endpoints; resolve them before implementation.

### Authentication
- Wire the JWT `api` guard (currently only `web` exists in `config/auth.php`) and register `routes/api.php`
  in `bootstrap/app.php` — the foundation of every endpoint in this spec.
- May a Guest self-register as `Instructor` directly, or only as `Student` (Admin assigns instructors)?
- Changing own email: re-flag `email_verified_at` as null (needs resend+verify), or keep verification?
- Roles at registration are fixed — which roles are selectable by a guest?

### Users
- Who may delete/deactivate accounts, and does that affect owned courses/enrollments (cascade vs. block)?
- Is a "minimum one Admin" guard required when demoting a role?

### Categories
- Deleting a category referenced by courses: block with `409`, or prevent deletion while courses exist?

### Courses
- **`courses.status` enum mismatch** (`completed/in_progress/not_started` vs required `draft/published/archived`):
  rename/replace the column, introduce separate fields (`status`, `published_at`), or move those progress states
  to enrollments? Every course endpoint depends on this.
- Duplicate-title rule per instructor: add unique index `(user_id, title)` or rely on service validation?
- Deleting a course with active students: block, archive-only, or soft-delete (schema currently cascades)?

### Lessons
- Removing a completed lesson: schema cascades `progresses`; decide block vs. soft-delete vs. preserving rows.

### Enrollments
- Add unique composite index `(user_id, course_id)` to guarantee no double enrollment under concurrency?

### Assignments
- Is resubmission a per-assignment boolean flag (`allow_resubmission`), a per-submission version counter, or out of scope? PRD mentions "unless resubmission is allowed" but no column exists.
- Deleting an assignment with submissions: cascade (current) or block?

### Submissions
- Make `submissions.grade` nullable (required for "created then graded later") or keep a sentinel value?
- Add explicit `is_late` column, or derive from `submission_at > assignments.due_date` in the resource?

### Quizzes / Attempts
- Where is `max_attempts` stored (column on `quizes`)? PRD says configurable — currently absent.
- `attempts.status` enum lists `successed|failed`: correct to `passed|failed`/`succeeded|failed`, and does
  `score` need to be nullable for in-progress attempts?
- Can attempts be resumed, or is each attempt a single request (start + submit)?

### Questions
- **Auto-scoring is impossible without stored answers/options.** Where are `options` + `correct_answer`
  stored (`questions` columns vs. a new `question_options`/`answers` table)? Question type set beyond `mcq`?
- Should correct answers be excluded from any response a student can reach?

### Progress
- Define the completion formula weighting lessons, assignments, and quizzes (PRD requires reconciliation).
  Where is the aggregate percentage stored (`progresses` vs. a new `enrollments` column vs. computed)?
  `courses.status`'s progress-like enum — migrate it onto `enrollments`?

### Certificates
- What triggers issuance (automatic on 100% completion vs. Instructor/Admin manual)? Who may request?
- Uniqueness of `certificates.certificate_number`: add unique index or generate a per-course counter?

### Notifications
- **Table collision:** Laravel's `Notifiable` trait expects a polymorphic `notifications` table; the existing
  custom table uses scalar `user_id`. Adopt Laravel database-notifications (migrate), rename the custom table
  (e.g. `app_notifications`), or keep the custom model and drop the trait use?
- Define the exact trigger set (enrollment, grading, deadlines, certificate issuance) and payload fields
  (`type`, `title`, `message`).