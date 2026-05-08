# Requirements Document

## Introduction

This feature introduces a PTO (Paid Time Off) Leave Request and Approval system to the platform. Employees can submit leave requests specifying a date range and reason. Requests flow through a role-based approval chain: for Employee-submitted requests, HR must approve first, after which the request is forwarded to Admin for final approval. Admins and roles above (Super Admin) may bypass the HR step and directly approve or reject Employee requests. HR users who submit leave requests for themselves follow a separate chain: Admin approves first, then Super Admin provides final approval. Admin users who submit leave requests for themselves bypass the HR and Admin stages entirely - only Super Admin can approve or reject Admin self-submitted requests.

The feature integrates with the existing RBAC system (`super_admin` -> `admin` -> `hr` -> `employee` hierarchy) and follows the established pattern of dedicated pages for all CRUD operations - no modal dialogs or inline forms.

## Glossary

- **Leave_Request**: A formal request submitted by a User to take PTO for a specified date range, carrying a status that progresses through the approval chain.
- **Leave_Status**: The current state of a Leave_Request. Valid values are: `pending_hr`, `pending_admin`, `pending_super_admin`, `approved`, `rejected`, `cancelled`.
- **Approval_Action**: A record of a single approval or rejection decision made by an Approver on a Leave_Request, including the acting user, decision, optional comment, and timestamp.
- **Approval_Chain**: The ordered sequence of Approvers that must act on a Leave_Request before it reaches a terminal status (`approved` or `rejected`).
- **Approver**: A user authorised to approve or reject Leave_Requests at a given stage of the Approval_Chain.
- **Employee**: A user with the `employee` role as defined by the RBAC system.
- **HR**: A user with the `hr` role as defined by the RBAC system.
- **Admin**: A user with the `admin` role as defined by the RBAC system.
- **Super_Admin**: A user with the `super_admin` role as defined by the RBAC system.
- **Admin_Self_Request**: A Leave_Request submitted by an Admin user for themselves, which bypasses the HR and Admin approval stages and is routed directly to Super Admin for approval.
- **Leave_Manager**: The component responsible for creating, updating, cancelling, and listing Leave_Requests, and for processing Approval_Actions.
- **Access_Gate**: The middleware/policy layer from the RBAC system that enforces permission checks on every protected route or action.
- **Bypass_Approval**: The ability of an Admin or Super_Admin to approve an Employee's Leave_Request directly, skipping the HR approval stage. Bypass_Approval does not apply to HR self-submitted requests or Admin_Self_Requests.

---

## Requirements

### Requirement 1: Employee Leave Request Submission

**User Story:** As an employee, I want to submit a PTO leave request with a date range and reason, so that my absence can be reviewed and approved by the appropriate parties.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Leave_Manager SHALL allow the Employee to create a Leave_Request specifying a start date, an end date, and an optional reason.
2. THE Leave_Manager SHALL enforce that the start date is not in the past at the time of submission.
3. THE Leave_Manager SHALL enforce that the end date is on or after the start date.
4. WHEN an Employee submits a Leave_Request, THE Leave_Manager SHALL set the initial Leave_Status to `pending_hr`.
5. IF an Employee submits a Leave_Request with a date range that overlaps an existing non-rejected, non-cancelled Leave_Request for the same Employee, THEN THE Leave_Manager SHALL reject the submission and return a validation error identifying the conflicting request.
6. WHEN a Leave_Request is created, THE Leave_Manager SHALL record the submitting Employee's ID, the submission timestamp, and the initial Leave_Status.

---

### Requirement 2: HR Approval of Employee Leave Requests

**User Story:** As an HR user, I want to review and approve or reject pending employee leave requests, so that valid requests can proceed to the next approval stage.

#### Acceptance Criteria

1. WHILE authenticated as HR, THE Leave_Manager SHALL display only Leave_Requests submitted by Employee users with Leave_Status `pending_hr` in the HR approval queue, excluding Leave_Requests submitted by HR users or any higher role.
2. WHILE authenticated as HR, THE Leave_Manager SHALL allow approving a Leave_Request with Leave_Status `pending_hr` submitted by an Employee user.
3. WHEN an HR user approves a Leave_Request, THE Leave_Manager SHALL transition the Leave_Status from `pending_hr` to `pending_admin`.
4. WHILE authenticated as HR, THE Leave_Manager SHALL allow rejecting a Leave_Request with Leave_Status `pending_hr` submitted by an Employee user, requiring a rejection reason.
5. WHEN an HR user rejects a Leave_Request, THE Leave_Manager SHALL set the Leave_Status to `rejected` and record the rejection reason in the Approval_Action.
6. WHEN an HR user approves or rejects a Leave_Request, THE Leave_Manager SHALL record an Approval_Action containing the HR user's ID, the decision, an optional comment, and a timestamp.
7. IF an HR user attempts to approve or reject a Leave_Request that is not in `pending_hr` status, THEN THE Leave_Manager SHALL reject the action and return an error indicating the request is not in the correct state.
8. IF an HR user attempts to approve or reject a Leave_Request not submitted by an Employee user, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 3: Admin Approval of Employee Leave Requests

**User Story:** As an Admin, I want to review and approve or reject employee leave requests that have passed HR review, so that I can provide final authorisation for employee absences.

#### Acceptance Criteria

1. WHILE authenticated as Admin, THE Leave_Manager SHALL display all Employee Leave_Requests with Leave_Status `pending_admin` in the Admin approval queue.
2. WHILE authenticated as Admin, THE Leave_Manager SHALL allow approving an Employee Leave_Request with Leave_Status `pending_admin`.
3. WHEN an Admin approves an Employee Leave_Request with Leave_Status `pending_admin`, THE Leave_Manager SHALL set the Leave_Status to `approved`.
4. WHILE authenticated as Admin, THE Leave_Manager SHALL allow rejecting an Employee Leave_Request with Leave_Status `pending_admin`, requiring a rejection reason.
5. WHEN an Admin rejects an Employee Leave_Request, THE Leave_Manager SHALL set the Leave_Status to `rejected` and record the rejection reason in the Approval_Action.
6. WHEN an Admin approves or rejects an Employee Leave_Request, THE Leave_Manager SHALL record an Approval_Action containing the Admin's ID, the decision, an optional comment, and a timestamp.
7. IF an Admin attempts to approve or reject a Leave_Request that is not in `pending_admin` status, THEN THE Leave_Manager SHALL reject the action and return an error indicating the request is not in the correct state.

---

### Requirement 4: Admin and Super Admin Bypass Approval for Employee Requests

**User Story:** As an Admin or Super Admin, I want to directly approve or reject an employee's leave request without waiting for HR review, so that urgent or straightforward requests can be resolved quickly.

#### Acceptance Criteria

1. WHILE authenticated as Admin, THE Leave_Manager SHALL allow approving or rejecting any Employee Leave_Request regardless of its current Leave_Status, provided the status is not already `approved`, `rejected`, or `cancelled`.
2. WHILE authenticated as Super_Admin, THE Leave_Manager SHALL allow approving or rejecting any Employee Leave_Request regardless of its current Leave_Status, provided the status is not already `approved`, `rejected`, or `cancelled`.
3. WHEN an Admin or Super_Admin performs a Bypass_Approval on an Employee Leave_Request, THE Leave_Manager SHALL set the Leave_Status to `approved` or `rejected` accordingly, skipping any remaining Approval_Chain stages.
4. WHEN a Bypass_Approval is performed, THE Leave_Manager SHALL record an Approval_Action containing the acting user's ID, the decision, an optional comment, a flag indicating the action was a bypass, and a timestamp.
5. IF an Admin or Super_Admin attempts to act on a Leave_Request with Leave_Status `approved`, `rejected`, or `cancelled`, THEN THE Leave_Manager SHALL reject the action and return an error indicating the request is already in a terminal state.

---

### Requirement 5: HR Self-Submitted Leave Request Flow

**User Story:** As an HR user, I want to submit a PTO leave request for myself, so that my own absences are formally tracked and approved by the appropriate higher-level roles.

#### Acceptance Criteria

1. WHILE authenticated as HR, THE Leave_Manager SHALL allow the HR user to create a Leave_Request for themselves specifying a start date, an end date, and an optional reason.
2. THE Leave_Manager SHALL enforce that the start date is not in the past at the time of submission.
3. THE Leave_Manager SHALL enforce that the end date is on or after the start date.
4. WHEN an HR user submits a Leave_Request for themselves, THE Leave_Manager SHALL set the initial Leave_Status to `pending_admin`.
5. IF an HR user submits a Leave_Request with a date range that overlaps an existing non-rejected, non-cancelled Leave_Request for the same HR user, THEN THE Leave_Manager SHALL reject the submission and return a validation error identifying the conflicting request.
6. THE Leave_Manager SHALL NOT allow an HR user to approve their own Leave_Request at any stage of the Approval_Chain.

---

### Requirement 6: Admin Approval of HR Leave Requests

**User Story:** As an Admin, I want to review and approve or reject leave requests submitted by HR users, so that HR absences are formally authorised before reaching Super Admin.

#### Acceptance Criteria

1. WHILE authenticated as Admin, THE Leave_Manager SHALL display all HR Leave_Requests with Leave_Status `pending_admin` in the Admin approval queue, alongside Employee Leave_Requests with Leave_Status `pending_admin`, but excluding Admin_Self_Requests.
2. WHILE authenticated as Admin, THE Leave_Manager SHALL allow approving a Leave_Request submitted by an HR user with Leave_Status `pending_admin`.
3. WHEN an Admin approves an HR Leave_Request with Leave_Status `pending_admin`, THE Leave_Manager SHALL transition the Leave_Status to `pending_super_admin`.
4. WHILE authenticated as Admin, THE Leave_Manager SHALL allow rejecting an HR Leave_Request with Leave_Status `pending_admin`, requiring a rejection reason.
5. WHEN an Admin rejects an HR Leave_Request, THE Leave_Manager SHALL set the Leave_Status to `rejected` and record the rejection reason in the Approval_Action.
6. WHEN an Admin approves or rejects an HR Leave_Request, THE Leave_Manager SHALL record an Approval_Action containing the Admin's ID, the decision, an optional comment, and a timestamp.
7. IF an Admin attempts to approve or reject an Admin_Self_Request, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 7: Admin Self-Submitted Leave Request Flow

**User Story:** As an Admin, I want to submit a PTO leave request for myself, so that my own absences are formally tracked and require Super Admin authorisation.

#### Acceptance Criteria

1. WHILE authenticated as Admin, THE Leave_Manager SHALL allow the Admin user to create a Leave_Request for themselves specifying a start date, an end date, and an optional reason.
2. THE Leave_Manager SHALL enforce that the start date is not in the past at the time of submission.
3. THE Leave_Manager SHALL enforce that the end date is on or after the start date.
4. WHEN an Admin user submits a Leave_Request for themselves, THE Leave_Manager SHALL set the initial Leave_Status to `pending_super_admin`.
5. IF an Admin user submits a Leave_Request with a date range that overlaps an existing non-rejected, non-cancelled Leave_Request for the same Admin user, THEN THE Leave_Manager SHALL reject the submission and return a validation error identifying the conflicting request.
6. THE Leave_Manager SHALL NOT allow an Admin user to approve their own Leave_Request at any stage of the Approval_Chain.
7. THE Leave_Manager SHALL NOT display Admin_Self_Requests in the Admin approval queue.

---

### Requirement 8: Super Admin Final Approval of HR and Admin Leave Requests

**User Story:** As the Super Admin, I want to provide final approval or rejection for HR leave requests approved by Admin and for Admin self-submitted leave requests, so that these absences receive the highest level of authorisation.

#### Acceptance Criteria

1. WHILE authenticated as Super_Admin, THE Leave_Manager SHALL display all Leave_Requests with Leave_Status `pending_super_admin` in the Super Admin approval queue, including both HR Leave_Requests forwarded by Admin and Admin_Self_Requests.
2. WHILE authenticated as Super_Admin, THE Leave_Manager SHALL allow approving a Leave_Request with Leave_Status `pending_super_admin`.
3. WHEN the Super_Admin approves a Leave_Request with Leave_Status `pending_super_admin`, THE Leave_Manager SHALL set the Leave_Status to `approved`.
4. WHILE authenticated as Super_Admin, THE Leave_Manager SHALL allow rejecting a Leave_Request with Leave_Status `pending_super_admin`, requiring a rejection reason.
5. WHEN the Super_Admin rejects a Leave_Request with Leave_Status `pending_super_admin`, THE Leave_Manager SHALL set the Leave_Status to `rejected` and record the rejection reason in the Approval_Action.
6. WHEN the Super_Admin approves or rejects a Leave_Request, THE Leave_Manager SHALL record an Approval_Action containing the Super_Admin's ID, the decision, an optional comment, and a timestamp.
7. IF the Super_Admin attempts to approve or reject a Leave_Request that is not in `pending_super_admin` status, THEN THE Leave_Manager SHALL reject the action and return an error indicating the request is not in the correct state.

---

### Requirement 9: Leave Request Cancellation

**User Story:** As a user, I want to cancel my own pending leave request, so that I can withdraw a request that is no longer needed before it is acted upon.

#### Acceptance Criteria

1. WHILE authenticated as Employee, HR, or Admin, THE Leave_Manager SHALL allow the submitting user to cancel their own Leave_Request provided the Leave_Status is not `approved`, `rejected`, or `cancelled`.
2. WHEN a user cancels their own Leave_Request, THE Leave_Manager SHALL set the Leave_Status to `cancelled` and record the cancellation timestamp.
3. IF a user attempts to cancel a Leave_Request with Leave_Status `approved`, `rejected`, or `cancelled`, THEN THE Leave_Manager SHALL reject the action and return an error indicating the request cannot be cancelled in its current state.
4. IF a user attempts to cancel another user's Leave_Request, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 10: Leave Request Listing and Detail Pages

**User Story:** As a user, I want to view my own leave requests and their current status, so that I can track the progress of my submissions through the approval chain.

#### Acceptance Criteria

1. WHILE authenticated as Employee, HR, or Admin, THE Leave_Manager SHALL provide a dedicated index page listing all Leave_Requests submitted by the authenticated user, showing the date range, reason, current Leave_Status, and submission date for each request.
2. THE Leave_Manager SHALL provide a dedicated detail page for each Leave_Request that displays the full request details and the complete Approval_Action history in chronological order.
3. WHILE authenticated as HR, THE Leave_Manager SHALL provide a dedicated approval queue page listing only Employee Leave_Requests pending HR action (Leave_Status `pending_hr`), showing the submitting user's name, date range, reason, and current Leave_Status.
4. WHILE authenticated as Admin, THE Leave_Manager SHALL provide a dedicated approval queue page listing Employee Leave_Requests and HR Leave_Requests with Leave_Status `pending_admin`, excluding Admin_Self_Requests, showing the submitting user's name, date range, reason, and current Leave_Status.
5. WHILE authenticated as Super_Admin, THE Leave_Manager SHALL provide a dedicated approval queue page listing all Leave_Requests with Leave_Status `pending_super_admin` (including HR Leave_Requests and Admin_Self_Requests), showing the submitting user's name, date range, reason, and current Leave_Status.
6. WHILE authenticated as Admin or Super_Admin, THE Leave_Manager SHALL provide a dedicated page listing all Leave_Requests across all users, with filters for Leave_Status, submitting user role, and date range.
7. THE Leave_Manager SHALL allow filtering the personal leave request index by Leave_Status.
8. WHEN a Leave_Request detail page is viewed by an Approver, THE Leave_Manager SHALL display the approve and reject action controls if the Leave_Request is in a status that the Approver is authorised to act upon.
9. WHILE authenticated as Admin, THE Leave_Manager SHALL NOT display Admin_Self_Requests submitted by other Admin users in the Admin approval queue.
10. WHILE authenticated as Admin or Super_Admin, THE Leave_Manager SHALL allow viewing the detail page of any Leave_Request, including Admin_Self_Requests, via the all-requests listing page.

---

### Requirement 11: Dedicated Pages for Leave Request Operations

**User Story:** As a user, I want each leave management action to have its own dedicated page, so that the interface is clear and consistent with the rest of the application.

#### Acceptance Criteria

1. THE Leave_Manager SHALL provide a dedicated page for submitting a new Leave_Request, accessible via a link from the personal leave request index page.
2. THE Leave_Manager SHALL provide a dedicated detail page for viewing a Leave_Request at `/leave-requests/{leaveRequest}`, accessible via a link from the index and queue pages.
3. THE Leave_Manager SHALL provide a dedicated page for the personal leave request index at `/leave-requests`.
4. THE Leave_Manager SHALL provide a dedicated page for the approval queue at `/leave-requests/approvals`.
5. THE Leave_Manager SHALL NOT use modal dialogs or inline forms for any Leave_Request create, approve, or reject operation.
6. WHEN a Leave_Request submission is completed successfully, THE Leave_Manager SHALL redirect the submitting user to the personal leave request index page.
7. WHEN an Approval_Action is completed successfully, THE Leave_Manager SHALL redirect the Approver to the approval queue page.

---

### Requirement 12: Access Control for Leave Management

**User Story:** As a platform administrator, I want leave management permissions to align with the existing role hierarchy, so that access is consistently enforced across the application.

#### Acceptance Criteria

1. THE Access_Gate SHALL allow only authenticated users to access any leave management route.
2. THE Access_Gate SHALL allow Employee, HR, and Admin users to submit Leave_Requests for themselves only.
3. THE Access_Gate SHALL allow HR users to approve or reject only Leave_Requests with Leave_Status `pending_hr` submitted by Employee users; HR users SHALL NOT approve or reject Leave_Requests submitted by HR users or any higher role.
4. THE Access_Gate SHALL allow Admin users to approve or reject Leave_Requests with Leave_Status `pending_admin` submitted by Employee or HR users, and to perform Bypass_Approval on any Employee Leave_Request not in a terminal status; Admin users SHALL NOT approve or reject Admin_Self_Requests.
5. THE Access_Gate SHALL allow Super_Admin users to approve or reject Leave_Requests with Leave_Status `pending_super_admin` (including HR Leave_Requests and Admin_Self_Requests), and to perform Bypass_Approval on any Employee Leave_Request not in a terminal status.
6. THE Access_Gate SHALL restrict visibility of Admin_Self_Requests to the submitting Admin and Super_Admin users only; HR users and other Admin users SHALL NOT view Admin_Self_Requests.
7. IF an unauthenticated user attempts to access any leave management route, THEN THE Access_Gate SHALL redirect the request to the login page.
8. IF a user attempts to perform an approval action on a Leave_Request for which their role is not the designated Approver at the current stage, THEN THE Access_Gate SHALL return an HTTP 403 response.
9. THE Access_Gate SHALL prevent any user from approving their own Leave_Request at any stage of the Approval_Chain.
