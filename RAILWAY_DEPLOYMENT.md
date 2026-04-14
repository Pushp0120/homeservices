# Railway.app Deployment Guide

## Overview
This guide will help you deploy your Home Services PHP application on Railway.app.

## Prerequisites
- Railway.app account
- GitHub account (for Git integration)
- Your project pushed to a GitHub repository

## Step 1: Prepare Your Repository

1. **Push your code to GitHub:**
   ```bash
   git add .
   git commit -m "Add Railway deployment configuration"
   git push origin main
   ```

2. **Ensure all deployment files are committed:**
   - `Dockerfile`
   - `railway.toml`
   - `.dockerignore`
   - Updated `config/database.php`
   - Updated `config/app.php`

## Step 2: Deploy on Railway

1. **Create a new project on Railway.app**
   - Go to [railway.app](https://railway.app)
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your repository

2. **Configure Environment Variables**
   
   In your Railway project settings, add these environment variables:
   
   **Database Variables (Railway MySQL Plugin):**
   - `DB_HOST` - Railway provides this automatically
   - `DB_USER` - Railway provides this automatically  
   - `DB_PASSWORD` - Railway provides this automatically
   - `DB_NAME` - Railway provides this automatically
   - `DB_PORT` - Railway provides this automatically

   **Application Variables:**
   - `RAILWAY_ENVIRONMENT` - Set to `production`

3. **Add MySQL Database Plugin**
   - In your Railway project, click "New"
   - Select "MySQL" from the plugins
   - Railway will automatically set the database environment variables

4. **Import Your Database**
   
   **Option A: Using Railway CLI**
   ```bash
   # Install Railway CLI
   npm install -g @railway/cli
   
   # Login to Railway
   railway login
   
   # Import your SQL file
   railway variables
   railway mysql import homeservices.sql
   ```

   **Option B: Using MySQL Workbench/CLI**
   - Get database credentials from Railway variables
   - Connect using MySQL client
   - Import your `homeservices.sql` file

## Step 3: Configure Deployment

1. **Verify Railway.toml Configuration**
   - The `railway.toml` file is already configured for your app
   - Railway will automatically detect the Dockerfile

2. **Set Health Check**
   - Health check path is set to `/` in railway.toml
   - This ensures Railway knows your app is running

## Step 4: Deploy and Test

1. **Automatic Deployment**
   - Railway will automatically build and deploy your app
   - Monitor the build logs for any issues

2. **Test Your Application**
   - Visit your Railway URL
   - Test user registration, login, and booking functionality
   - Verify database connectivity

3. **Troubleshooting Common Issues**
   
   **Database Connection Issues:**
   - Verify all DB environment variables are set
   - Check if database plugin is properly attached
   - Review build logs for connection errors

   **File Upload Issues:**
   - Ensure uploads directory has proper permissions
   - Check if upload path is accessible

   **URL/Redirect Issues:**
   - Verify APP_URL is correctly set
   - Check HTTPS/HTTP scheme detection

## Environment Variables Reference

| Variable | Description | Railway Source |
|----------|-------------|----------------|
| `DB_HOST` | Database hostname | MySQL Plugin |
| `DB_USER` | Database username | MySQL Plugin |
| `DB_PASSWORD` | Database password | MySQL Plugin |
| `DB_NAME` | Database name | MySQL Plugin |
| `DB_PORT` | Database port | MySQL Plugin |
| `RAILWAY_ENVIRONMENT` | Environment type | Manual |

## Post-Deployment Checklist

- [ ] Application loads correctly
- [ ] Database connection works
- [ ] User registration/login functions
- [ ] File uploads work properly
- [ ] All pages load without errors
- [ ] HTTPS is working (automatic on Railway)
- [ ] Custom domain is configured (if needed)

## Custom Domain Setup (Optional)

1. In Railway project settings, click "Domains"
2. Add your custom domain
3. Configure DNS records as instructed by Railway
4. Wait for SSL certificate provisioning

## Monitoring and Logs

- View real-time logs in Railway dashboard
- Monitor performance metrics
- Set up alerts for errors or downtime

## Support

For Railway-specific issues:
- Check Railway documentation at docs.railway.app
- Contact Railway support through their dashboard

For application-specific issues:
- Review your application logs
- Check database schema and data
- Verify file permissions and paths
