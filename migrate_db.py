import sqlite3
import os

def migrate_database():
    db_path = 'instance/inspiranet.db'
    
    if not os.path.exists(db_path):
        print("Database doesn't exist. It will be created on first run.")
        return
    
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    
    try:
        cursor.execute("PRAGMA table_info(users)")
        columns = [col[1] for col in cursor.fetchall()]
        
        if 'profile_photo' not in columns:
            print("Adding profile_photo column to users table...")
            cursor.execute("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255)")
            conn.commit()
            print("✓ Added profile_photo column")
        else:
            print("✓ profile_photo column already exists")
        
        cursor.execute("PRAGMA table_info(exam_attempts)")
        columns = [col[1] for col in cursor.fetchall()]
        
        if 'essay_answers' not in columns:
            print("Adding essay_answers column to exam_attempts table...")
            cursor.execute("ALTER TABLE exam_attempts ADD COLUMN essay_answers TEXT")
            conn.commit()
            print("✓ Added essay_answers column")
        else:
            print("✓ essay_answers column already exists")
        
        print("\nDatabase migration completed successfully!")
        
    except Exception as e:
        print(f"Error during migration: {e}")
        conn.rollback()
    finally:
        conn.close()

if __name__ == '__main__':
    migrate_database()
