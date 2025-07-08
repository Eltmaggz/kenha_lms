# KeNHA LMS

A role-based Learning Management System (LMS) designed for the **Kenya National Highways Authority (KeNHA)**. The portal manages internal staff training programs, departmental oversight, HR scheduling, and Active Directory-based user access.


---

## 📌 Features

- 🔒 Secure login with session-based role management (`Staff`, `HOD`, `HR Manager`)
- 🗂️ Training module creation and assignment by HR and HODs
- 📅 Yearly training planning based on financial years
- 🧾 Staff eligibility validation per training
- 🗃️ Training history logs per employee
- 🖥️ Custom dashboards for:
  - HR Managers (all departments/regions)
  - HODs (department-specific)
  - Staff (personal training schedule & requests)

---

## 🛠️ Tech Stack

| Category       | Tools/Tech                            |
|----------------|----------------------------------------|
| **Frontend**   | HTML5, CSS3, JavaScript                |
| **Backend**    | PHP                                    |
| **Database**   | MySQL                                  |
| **Tools**      | XAMPP, VS Code, Git                    |
| **Design**     | Tailwind CSS (or custom CSS), Figma (UI draft) |

---

## 🏗️ System Architecture

+-----------+ +------------+ +------------+
| Staff |<------>| Backend |<-----> | MySQL DB |
+-----------+ +------------+ +------------+
|
+-----------+
| HOD |
+-----------+
|
+-----------+
| HR Admin |
+-----------+


---

## 👥 User Roles & Permissions

| Role         | Permissions |
|--------------|-------------|
| **Staff**    | View & register for trainings, track progress |
| **HOD**      | Approve staff trainings, schedule departmental trainings |
| **HR Manager** | Create & manage trainings for all departments, generate reports |

---

