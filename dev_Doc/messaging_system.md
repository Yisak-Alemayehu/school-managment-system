# Messaging System (Communication Module)

This document provides a full explanation of the **Messaging system** in this SMS application.

It covers:
- Database schema (tables + relationships)
- UI pages and routes
- Backend logic (message sending, notifications, status tracking)
- Group messaging, attachments, and conversation tracking
- Common use cases and extension points

---

## 0. Routes (URLs) & Permissions

The Communication module is routed via `modules/communication/routes.php`, controlled by `action`.

### 0.1 Main pages
- `?module=communication&action=announcements` — Announcements list
- `?module=communication&action=announcement-create` — Create announcement
- `?module=communication&action=announcement-edit&id=<id>` — Edit announcement
- `?module=communication&action=announcement-view&id=<id>` — View announcement
- `?module=communication&action=messages` / `inbox` — Messages inbox
- `?module=communication&action=message-compose` — Compose message
- `?module=communication&action=message-view&id=<id>` — View single message thread
- `?module=communication&action=sent` — Sent messages
- `?module=communication&action=notifications` — Notifications list

### 0.2 Actions (POST)
- `?module=communication&action=announcement-save` — Save announcement
- `?module=communication&action=announcement-delete` — Delete announcement
- `?module=communication&action=message-send` — Send a message
- `?module=communication&action=notification-read` — Mark notification read
- `?module=communication&action=notifications-read-all` — Mark all notifications read

### Permissions
- Announcements creation/editing requires `communication.create`.
- Announcement deletion requires `communication.delete`.
- Message send/view uses no explicit permission check in the routing file (assumes authenticated users).

---

## 1. Database Schema

### 1.1 Conversations & Messaging (Advanced schema - archived)

A full messaging schema exists in `sql/archive/messaging.sql` (not currently in production db by default). It supports:
- Solo (user-to-user), bulk, and group messaging
- Message attachments
- Per-recipient delivery/read tracking

Key tables:
- `msg_conversations` — conversation containers (type: solo/bulk/group)
- `msg_conversation_participants` — who is in each conversation
- `msg_messages` — message content
- `msg_message_status` — per-recipient status (sent/delivered/read)
- `msg_attachments` — file attachments
- `msg_groups` — student groups for group messaging
- `msg_group_members` — members of a group

### 1.2 Active Messaging Schema (current)

The current implementation in `modules/communication` uses a simpler schema:

#### `messages`
- `id`, `sender_id`, `receiver_id` (user IDs)
- `subject`, `body`
- `is_read` (0/1)
- `created_at`

#### `notifications`
- `id`, `user_id` (recipient)
- `type` (e.g., `message`)
- `title`, `message`, `link`
- `is_read`
- `created_at`

> Note: The table naming conventions above come from `modules/communication/actions/message_send.php`.

---

## 2. Workflow & Logic

### 2.1 Sending a Message

**UI:** `?module=communication&action=message-compose`

**Action:** `?module=communication&action=message-send` → `modules/communication/actions/message_send.php`

**What happens (step-by-step):**
1. `verify_csrf()` ensures form authenticity.
2. Validates fields:
   - `receiver_id` required + numeric
   - `subject` required
   - `body` required
3. Prevents sending to self.
4. Confirms the receiver is an active user.
5. Inserts into `messages`:
   - `sender_id`, `receiver_id`, `subject`, `body`, `is_read = 0`
6. Inserts a `notifications` record for the receiver:
   - `type = 'message'`
   - `title` = 'New message from <sender name>'
   - `link` points to `?module=communication&action=message-view&id=<message_id>`
7. Logs action via `audit_log('message_send', 'messages', $msgId)`.

### 2.2 Viewing Messages

**Inbox:** `?module=communication&action=inbox` (view list of received messages)

**Sent:** `?module=communication&action=sent` (view list of sent messages)

**Message View:** `?module=communication&action=message-view&id=<id>`
- Displays message content and allows replies (if implemented).

### 2.3 Notifications

**Mark as read:** `?module=communication&action=notification-read` (action updates `notifications.is_read`)

**Mark all read:** `?module=communication&action=notifications-read-all`

---

## 3. Common Use Cases

### 3.1 Send a direct message to another user
1. Go to **Messages → Compose**.
2. Select recipient, enter subject & body.
3. Send.
4. Recipient receives a notification and the message appears in their inbox.

### 3.2 View message thread
1. Go to **Messages → Inbox**.
2. Click a message to view details.
3. Replies may be supported depending on implementation.

### 3.3 Manage announcements
1. Create an announcement under **Announcements**.
2. View or delete existing announcements.

---

## 4. Extension Points (How to upgrade to full conversation system)

To use the full archived messaging schema (`msg_*` tables), you can:
1. Import `sql/archive/messaging.sql` into your database.
2. Update the communication module to use `msg_conversations`, `msg_messages`, and `msg_message_status`.
3. Add UI for bulk/group messaging (using `msg_groups`).

---

**End of Messaging System documentation.**
