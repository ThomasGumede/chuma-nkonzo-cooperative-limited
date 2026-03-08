#!/usr/bin/env python3
import re
import os

# Read CSS
try:
    with open("assets/css/main.css", "r", encoding="utf-8", errors="ignore") as f:
        css_text = f.read()
except:
    print("Error reading CSS file")
    exit(1)

# Extract classes and IDs with line numbers - more efficient
classes_dict = {}
ids_dict = {}
lines = css_text.split('\n')

for line_num, line in enumerate(lines, 1):
    # Find class selectors with word boundary
    for m in re.finditer(r'\.([a-zA-Z_][\w-]*)', line):
        cls = m.group(1)
        if cls not in classes_dict:
            classes_dict[cls] = []
        classes_dict[cls].append(line_num)
    
    # Find ID selectors
    for m in re.finditer(r'#([a-zA-Z_][\w-]*)', line):
        id_sel = m.group(1)
        if id_sel not in ids_dict:
            ids_dict[id_sel] = []
        ids_dict[id_sel].append(line_num)

print(f"Found {len(classes_dict)} unique classes")
print(f"Found {len(ids_dict)} unique IDs")

# Load HTML files
html_content = ""
for root, dirs, files in os.walk("."):
    for file in files:
        if file.endswith(".html"):
            try:
                with open(os.path.join(root, file), "r", encoding="utf-8", errors="ignore") as f:
                    html_content += f.read() + "\n"
            except:
                pass

print(f"Loaded HTML files, total size: {len(html_content)} bytes")

# Check which ones are used (more lenient check)
unused_classes = []
for cls in sorted(classes_dict.keys()):
    # Check if used in HTML with word boundaries
    if not re.search(r'[\s"\']' + re.escape(cls) + r'[\s"\'\-]', html_content):
        unused_classes.append((cls, classes_dict[cls]))

unused_ids = []
for id_sel in sorted(ids_dict.keys()):
    if not re.search(r'[\s"\']' + re.escape(id_sel) + r'[\s"\']', html_content):
        unused_ids.append((id_sel, ids_dict[id_sel]))

print(f"Unused classes: {len(unused_classes)}")
print(f"Unused IDs: {len(unused_ids)}")

# Write report
with open("css_unused_report.txt", "w", encoding="utf-8") as out:
    out.write("=" * 90 + "\n")
    out.write("CSS UNUSED SELECTORS ANALYSIS REPORT\n")
    out.write("=" * 90 + "\n\n")
    
    out.write(f"CSS Analysis Summary:\n")
    out.write(f"  Total CSS Classes: {len(classes_dict)}\n")
    out.write(f"  Used Classes: {len(classes_dict) - len(unused_classes)}\n")
    out.write(f"  Unused Classes: {len(unused_classes)}\n\n")
    out.write(f"  Total CSS IDs: {len(ids_dict)}\n")
    out.write(f"  Used IDs: {len(ids_dict) - len(unused_ids)}\n")
    out.write(f"  UNUSED IDs: {len(unused_ids)}\n\n")
    
    out.write("=" * 90 + "\n")
    out.write("UNUSED CLASS SELECTORS - Quick Reference\n")
    out.write("=" * 90 + "\n\n")
    
    for cls, lines in unused_classes:
        unique_lines = sorted(set(lines))
        line_str = f"{min(unique_lines)}"
        if len(unique_lines) > 1:
            line_str += f"-{max(unique_lines)}"
        out.write(f".{cls:<60} Line(s): {line_str}\n")
    
    out.write("\n" + "=" * 90 + "\n")
    out.write("UNUSED ID SELECTORS\n")
    out.write("=" * 90 + "\n\n")
    
    if unused_ids:
        for id_sel, lines in unused_ids:
            unique_lines = sorted(set(lines))
            line_str = f"{min(unique_lines)}"
            if len(unique_lines) > 1:
                line_str += f"-{max(unique_lines)}"
            out.write(f"#{id_sel:<60} Line(s): {line_str}\n")
    else:
        out.write("All ID selectors are used!\n")

print("\nReport written to css_unused_report.txt")

# Print summary
print("\n" + "=" * 90)
print("TOP 50 UNUSED CLASSES (for reference)")
print("=" * 90)
for i, (cls, lines) in enumerate(unused_classes[:50], 1):
    print(f"{i:2}. .{cls:<55} Line {min(lines)}")

if len(unused_classes) > 50:
    print(f"\n... and {len(unused_classes) - 50} more unused classes")
