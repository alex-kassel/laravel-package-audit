# 🛡️ Package Audit Dashboard

Centralized pre-release audit status registry for packages in the workspace.
Automated by the [Lead Audit Orchestrator](orchestrator.md) following the [Laravel Package Audit Framework](README.md).

---

## 📊 Package Audit Status

| Package | Last Audited | Duration | Target Version | Verdict | Blockers | Critical | Major | Pending Decisions | Latest Report |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|---|
| *`<vendor>/<package-name>`* | *Not yet audited* | - | `1.0.0` | ⚪ PENDING | - | - | - | - | - |

---

## 🧭 Verdict Legend
- 🟢 **READY**: No known release-blocking issues were found, and all required verification gates passed.
- 🟡 **CONDITIONAL**: 0 Blockers, non-critical decisions pending in `decisions.md` or acceptable documented risks.
- 🔴 **BLOCKED**: >= 1 Blocker or Critical defect, broken installation/discovery, or failed essential audit.
- ⚪ **PENDING**: Package has not undergone audit yet.
