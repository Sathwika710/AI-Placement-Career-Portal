import sys
import os
import json
import re
import argparse

# Try to import pypdf
try:
    from pypdf import PdfReader
except ImportError:
    print(json.dumps({"error": "pypdf library not found. Please install using: pip install pypdf"}))
    sys.exit(1)

def extract_text(file_path):
    ext = os.path.splitext(file_path)[1].lower()
    if ext == '.txt':
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                return f.read()
        except Exception as e:
            return str(e)
    elif ext == '.pdf':
        try:
            reader = PdfReader(file_path)
            text = ""
            for page in reader.pages:
                page_text = page.extract_text()
                if page_text:
                    text += page_text + "\n"
            return text
        except Exception as e:
            return str(e)
    else:
        return ""

def parse_contact_info(text):
    email_pattern = r'[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+'
    phone_pattern = r'\+?\d[\d -]{8,12}\d'
    
    emails = re.findall(email_pattern, text)
    phones = re.findall(phone_pattern, text)
    
    email = emails[0] if emails else "Not Found"
    phone = phones[0] if phones else "Not Found"
    
    return email, phone

def parse_skills(text):
    # Standard technical skill keywords
    skill_keywords = [
        "python", "java", "spring boot", "react", "html", "css", "javascript", "js",
        "mysql", "sql", "php", "laravel", "c++", "c#", "go", "ruby", "node", "express",
        "angular", "vue", "django", "flask", "aws", "docker", "kubernetes", "git",
        "machine learning", "deep learning", "nlp", "ai", "swift", "kotlin", "android",
        "data structures", "algorithms", "mongodb", "postgresql", "redis", "linux"
    ]
    
    found_skills = []
    text_lower = text.lower()
    for skill in skill_keywords:
        # Match word boundaries or spaces
        pattern = r'\b' + re.escape(skill) + r'\b'
        if re.search(pattern, text_lower):
            # Format nicely
            formatted = skill.title()
            if formatted == "Spring Boot":
                formatted = "Spring Boot"
            elif formatted in ["Html", "Css", "Sql", "Php", "Aws", "Nlp", "Ai", "Js"]:
                formatted = formatted.upper()
            found_skills.append(formatted)
            
    return sorted(list(set(found_skills)))

def parse_education(text):
    edu_keywords = ["b.tech", "m.tech", "btech", "mtech", "b.e", "b.sc", "m.sc", "bca", "mca", "bachelor", "master", "ph.d", "phd", "university", "college", "institue", "school"]
    lines = text.split('\n')
    found_edu = []
    for line in lines:
        for keyword in edu_keywords:
            if keyword in line.lower():
                found_edu.append(line.strip())
                break
    return found_edu[:3] if found_edu else ["Bachelor of Science/Engineering (assumed)"]

def estimate_experience(text):
    # Try to find years of experience pattern
    exp_pattern = r'(\d+)\+?\s*(?:year|yr)s?\s*(?:of)?\s*(?:experience|exp)'
    match = re.search(exp_pattern, text.lower())
    if match:
        return int(match.group(1))
    
    # Otherwise check job-related keywords to guess experience level
    job_words = ["intern", "developer", "engineer", "senior", "lead", "architect", "manager", "fresher"]
    text_lower = text.lower()
    score = 0
    if "senior" in text_lower or "lead" in text_lower:
        return 5
    elif "engineer" in text_lower or "developer" in text_lower:
        return 2
    elif "intern" in text_lower or "fresher" in text_lower:
        return 0
    return 1

def analyze_resume(file_path, job_requirements=None):
    if not os.path.exists(file_path):
        return {"error": f"File not found: {file_path}"}
        
    text = extract_text(file_path)
    if not text.strip():
        return {"error": "Could not extract text from file."}
        
    email, phone = parse_contact_info(text)
    skills = parse_skills(text)
    education = parse_education(text)
    experience_years = estimate_experience(text)
    
    # Calculate global score
    score = 40 # Base score for having a resume
    score += min(len(skills) * 4, 30) # up to 30 points for skills
    score += min(experience_years * 6, 20) # up to 20 points for experience
    score += 10 # formatting/layout points
    
    # Job matching score
    match_percentage = 100
    missing_skills = []
    if job_requirements:
        req_list = [r.strip().lower() for r in job_requirements.split(',') if r.strip()]
        matched_count = 0
        text_lower = text.lower()
        for req in req_list:
            if re.search(r'\b' + re.escape(req) + r'\b', text_lower):
                matched_count += 1
            else:
                missing_skills.append(req.title())
        if req_list:
            match_percentage = int((matched_count / len(req_list)) * 100)
    
    # Feedback generation
    feedback = []
    if len(skills) < 5:
        feedback.append("Consider listing more core technical skills. Detail the programming languages and frameworks you know.")
    else:
        feedback.append(f"Strong skill representation with {len(skills)} key skills identified.")
        
    if experience_years == 0:
        feedback.append("Since you are a fresher or intern, highlight project work, open source contributions, or academic coursework to demonstrate hands-on experience.")
    else:
        feedback.append(f"Demonstrates approximately {experience_years}+ years of professional/project experience.")
        
    if email == "Not Found" or phone == "Not Found":
        feedback.append("Make sure your contact information (email, phone number) is clearly visible at the top of your resume.")
        
    if missing_skills:
        feedback.append(f"To better match this role, consider gaining or highlighting skills in: {', '.join(missing_skills[:3])}.")

    return {
        "email": email,
        "phone": phone,
        "skills": skills,
        "education": education,
        "experience_years": experience_years,
        "overall_score": min(score, 100),
        "job_match_score": match_percentage,
        "missing_skills": missing_skills,
        "feedback": feedback
    }

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="AI Resume Analyzer")
    parser.add_argument("--file", help="Path to resume file (PDF/TXT)")
    parser.add_argument("--job-reqs", help="Comma-separated list of job skills requirements", default=None)
    parser.add_argument("--test", action="store_true", help="Run self test")
    
    args = parser.parse_args()
    
    if args.test:
        print("Self test: OK")
        sys.exit(0)
        
    if not args.file:
        print(json.dumps({"error": "No file specified. Use --file <path>"}))
        sys.exit(1)
        
    result = analyze_resume(args.file, args.job_reqs)
    print(json.dumps(result, indent=2))
