# Resident-upload serving strategy

Resident-uploaded files must remain private by default. The Laravel `local` disk points to `storage/app/private` and direct serving is disabled.

When a resident-file feature is implemented:
1. Store the file on the private `local` disk; never place sensitive uploads on the public disk or a web-served directory.
2. Require authentication on the download route.
3. Authorize access with a Laravel policy for the owning resident and explicitly permitted Official/currently assigned Personnel roles.
4. Return the file through an application controller after authorization, or issue a short-lived signed URL only after the same authorization check.
5. Do not expose a directory listing, filesystem path, object key, or predictable public URL.
6. Log access to sensitive files as part of the audit trail introduced by the later audit phase.
7. Apply purpose limitation and consent before collecting vulnerability evidence, medical information, or identity documents.

Before enabling sensitive resident uploads, obtain Data Protection Officer/legal confirmation on consent wording, retention, access roles, lawful basis, breach handling, and data-subject rights under RA 10173.
