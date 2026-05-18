# 1. Create project directory and all files (copy the files above)
mkdir ehi-uploader
cd ehi-uploader
mkdir -p uploads

# 2. Create all the files above using your editor (nano, vim, VS Code etc.)
#    Then run:

# 3. Initialize git repo
git init

# 4. Add everything
git add .

# 5. Commit
git commit -m "Initial commit: EHI file uploader with secure storage"

# 6. Create a repo on GitHub (via browser), then connect:
git remote add origin https://github.com/YOUR_USERNAME/ehi-uploader.git

# 7. Push
git branch -M main
git push -u origin main
