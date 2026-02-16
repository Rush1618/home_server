# Persistent Storage Setup Guide

To ensure your data (images, uploads, logs, etc.) survives updates and environment resets, we use a persistent storage layer linked to your phone's shared storage.

## Step 1: Create folders in Termux

Run these commands in your **Termux shell** (exit Ubuntu first if you are inside it):

```bash
mkdir -p ~/storage/shared/platform_storage/admin/uploads
mkdir -p ~/storage/shared/platform_storage/admin/images
mkdir -p ~/storage/shared/platform_storage/admin/logs
mkdir -p ~/storage/shared/platform_storage/admin/env
mkdir -p ~/storage/shared/platform_storage/projects
```

## Step 2: Link storage in Ubuntu

Login to Ubuntu (`proot-distro login ubuntu`) and run:

```bash
# Define the root storage location
mkdir -p /srv/platform
ln -s /data/data/com.termux/files/home/storage/shared/platform_storage /srv/platform/storage

# Link Admin assets
ln -s /srv/platform/storage/admin/uploads /srv/platform/admin/uploads
ln -s /srv/platform/storage/admin/images /srv/platform/admin/images
ln -s /srv/platform/storage/admin/logs /srv/platform/admin/logs
ln -s /srv/platform/storage/admin/env /srv/platform/admin/env

# Link Project Root (if needed)
# ln -s /srv/platform/storage/projects /srv/platform/projects
```

## Why this is better

- **Git Safety**: When you `git pull`, your code updates but your images and logs stay untouched because they live in `/srv/platform/storage`.
- **Portability**: Your data lives on your phone's shared storage, making it easy to backup or access from other apps.
- **Persistence**: Even if you delete and reinstall the Ubuntu environment, your data remains safe in the Termux home folder.
