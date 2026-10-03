# KampusKonek User and Administrator Manual

KampusKonek keeps your council's events, delegated tasks, announcements, and adviser approvals in one place. This guide walks through each role, step by step.

## 1. Getting started (everyone)

### Create an account

1. Open the site and click **Sign up**.
2. Enter your full name, your **school email**, and a password (at least 8 characters).
3. Click **Create account**. There is no OTP and no email verification, you go straight in.

Your school email decides the kind of account you get:

| Email ends with | Who it is for | Starting account type |
|---|---|---|
| `@students.nu-laguna.edu.ph` | Students, student council officers, committee members | Student |
| `@admin.nu-laguna.edu.ph` or `@admin.nu-laguna-edu.ph` | Faculty, advisers, system admins, the Council President and VP | Adviser (no powers until assigned to an organization) |

Any other email is rejected. The form tells you right away if the domain isn't recognized.

### Log in

1. Click **Log in**, enter your school email and password.
2. If the email has no account yet, you are sent straight to **Sign up** with the email already filled in.
3. If you forgot your password, ask the system admin to reset it (see section 6). There is no email reset.

### Your profile

Click your name at the bottom of the left menu to change your display name or password. Your email can't be changed.

### Finding your way around

The left menu changes with your role:

| Menu item | Who sees it |
|---|---|
| Dashboard, Events, Calendar, Announcements, Organizations | Everyone |
| Tasks | Officers, committee members, advisers, admins |
| Approvals | Advisers, co-advisers, system admins. A gold number shows how many requests are waiting |
| Users | System admins |

On a phone, tap the menu button at the top left.

## 2. Roles at a glance

Your powers come from the **seat** you hold in an organization (Adviser, Co-Adviser, President, Vice President, Officer, or Committee Member), not only from your account type. One person can hold seats in several organizations.

| Action | Student | Committee member | Officer | President / VP | Adviser / Co-Adviser | System admin |
|---|---|---|---|---|---|---|
| View approved events, calendar, announcements | Yes | Yes | Yes | Yes | Yes | Yes |
| See drafts, pending items, and tasks of own organization | No | Yes | Yes | Yes | Yes | All organizations |
| Update status of a task given to you | No | Yes | Yes | Yes | Yes | Yes |
| Draft events, assign tasks, post updates | No | No | Yes | Yes | Yes | Yes |
| Edit any event or task in the organization | No | No | No | Yes | Yes | Yes |
| Create or change **restricted** tasks | No | No | No | Yes | Yes | Yes |
| Approve or disapprove events, tasks, posts | No | No | No | No | Yes | Yes |
| Manage organization members | No | No | No | Officers and committee only | Everyone except advisers | Everyone |
| Create organizations, manage users | No | No | No | No | No | Yes |

## 3. Students

You can read everything that has been approved.

1. **Dashboard** shows upcoming approved events and the latest announcements.
2. **Events** lists approved events from every organization. Use the filters (organization, upcoming or past) and the search box.
3. Click an event to see the date, time, venue, and description.
4. **Calendar** shows approved events by month. Use the arrows to change month. On phones the calendar becomes a list.
5. **Announcements** shows updates from councils and organizations. Use the organization filter to narrow it down.
6. **Organizations** shows each organization, its leadership, and its public updates.

Students can't see drafts, pending requests, or task lists.

## 4. Officers and committee members

### Before you can do anything

The adviser, the President or VP, or the system admin has to give you a seat. Until then your dashboard says "No organization seat yet". After you get a seat, your account type changes to Officer / Committee on its own.

### Draft an event

1. Go to **Events** and click **New event** (or use the button on your dashboard).
2. Pick the organization, then fill in title, description, venue, date, and optional start and end times. The date can't be in the past.
3. Choose one:
   - **Send for approval** puts it in the adviser's queue as *Awaiting approval*.
   - **Save as draft** keeps it private to your organization so you can finish it later.
4. Students see the event only after an adviser approves it.

### If your event is disapproved

1. Open the event. A red box shows the adviser's reason.
2. Click **Edit**, fix what they asked for, and choose **Save and send for approval**.

Editing an event that was already approved sends it back to *Awaiting approval*, so the adviser can check the change.

### Assign tasks

1. Open an event and click **Assign task** in the Task progress panel.
2. Enter the task, details, who it is for (any officer or committee member of your organization), priority, and deadline.
3. Click **Send for approval**.

An officer's task waits for the adviser. It shows an *Awaiting approval* badge and **does not count toward the event progress bar until approved**. You can edit your own task while it is waiting or after it was disapproved. Saving it sends it back to the adviser.

### Update your tasks

Tasks given to you appear on your dashboard and on **Tasks**. Change the status in the drop-down (To do, In progress, Done). The event progress bar updates right away.

You can't change a task that isn't approved yet, and you can't change a **restricted** task (marked with a padlock). Restricted tasks are for sensitive items such as budgets or permits. Ask the President, VP, or adviser to update them.

### Filter tasks

On **Tasks**, filter by assignee (Me or Anyone), organization, event, status, priority, approval, or tick **Overdue only**. Colors help you scan: a red edge means overdue, an amber edge means due within 2 days, green means done.

### Post an announcement

1. Open **Announcements** and click **Write an announcement**.
2. Choose your organization, write a title and message, and click **Post announcement**.
3. Posts from regular officers wait for adviser approval. Posts from the President, VP, advisers, and admins publish immediately.

## 5. Council President and Vice President

You have everything an officer has, plus override rights inside your own organization:

- Edit or delete any event or task, including ones other officers made.
- Create **restricted** tasks (tick *Restricted task* in the task form) and update restricted tasks.
- Your tasks are approved automatically, and so are your announcements.
- Add or remove **officers and committee members** from the organization page.

You can't approve events, tasks, or posts. That stays with the advisers. Your account must use an `@admin...` email.

## 6. Advisers and Co-Advisers

Every organization has at least one Adviser and one Co-Adviser, and both can approve.

### Review requests

1. Click **Approvals** (or use the queues on your dashboard).
2. You see three lists: event requests, task requests, and announcement requests for your organizations only.
3. For each item:
   - **Approve** (or **Publish** for announcements) makes it live.
   - **Disapprove** opens a box. Type a short reason (required) and click **Disapprove and send reason**. The officer sees your reason on the item.
4. **Recent event decisions** lists what you decided lately.

Approved events become visible to all students. Approved tasks start counting toward the event's progress bar.

### Create events yourself

Events you create are approved immediately. You can also edit or delete any event or task in your organizations.

### Manage members

Open your organization page. Under **Add or change a seat**, enter the person's school email and choose a position. They must have signed up first. You can assign President, Vice President, Officer, and Committee Member. Only the system admin assigns advisers.

## 7. System administrators

### Set up an organization

1. Open **Organizations** and click **New organization**.
2. Enter the name, acronym, type (council or organization), and a short description.
3. Choose an **Adviser** and a **Co-Adviser**. They must be two different people with `@admin...` accounts, and they must have signed up first. The form blocks you until both are chosen.
4. Click **Create organization**, then add the President, VP, officers, and members from the organization page.

If an organization ever ends up without an Adviser or Co-Adviser, a yellow warning appears on your dashboard and on the organization page. The system also refuses to remove the last Adviser or Co-Adviser. Assign a replacement first.

### Manage seats

On the organization page you can assign any position. Seat rules:

| Position | Email domain required |
|---|---|
| Adviser, Co-Adviser, President, Vice President | `@admin.nu-laguna.edu.ph` or `@admin.nu-laguna-edu.ph` |
| Officer, Committee Member | `@students.nu-laguna.edu.ph` |

Each organization can have one President and one Vice President.

### Manage users

Open **Users**. Search by name or email. For each person you can:

- **Make admin** or **Remove admin** (only `@admin...` accounts can be admins).
- **Deactivate** or **Activate**. Deactivated people can't log in.
- **Reset password**: type a new password and click **Set password**. Share it with the person privately and ask them to change it from their profile.

You can't deactivate yourself or remove your own admin access.

### Campus-wide announcements

On **Announcements**, choose **Campus-wide** in the *Post as* list. Only admins can do this.

### Edit or deactivate an organization

Open the organization page and click **Edit details**. Unticking *Active* hides it from lists.

## 8. Approval rules in one table

| Item | Created by | Starts as | Who approves | After approval |
|---|---|---|---|---|
| Event | Officer or President / VP | Draft or Awaiting approval | Adviser / Co-Adviser / Admin | Visible to all students |
| Event | Adviser or Admin | Approved | Not needed | Visible to all students |
| Task | Officer | Awaiting approval | Adviser / Co-Adviser / Admin | Counts toward progress |
| Task | President, VP, Adviser, Admin | Approved | Not needed | Counts toward progress |
| Announcement | Officer | Awaiting approval | Adviser / Co-Adviser / Admin | Published |
| Announcement | President, VP, Adviser, Admin | Published | Not needed | Published |

An approved event that gets edited by someone who isn't an adviser or admin goes back to *Awaiting approval*.

## 9. Common questions

**I can't see an event my friend told me about.** Only approved events are public. It may still be a draft or waiting for the adviser.

**My task isn't moving the progress bar.** Progress counts approved tasks only. Check that the task isn't still *Awaiting approval*.

**The status box on a task is missing.** Either the task isn't approved yet, it is restricted, or it isn't assigned to you.

**I get "You don't have access to this".** Your seat doesn't allow that action. Ask your adviser or the system admin.

**I can't add someone to my organization.** They need to have signed up first, and the email domain must match the position (see section 7).

**"Your session expired".** Go back, refresh the page, and try again.
