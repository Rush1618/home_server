<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projects.php';

require_login();

// Fetch Project Stats
$projectStats = get_project_stats();

// Fetch System Stats (Linux)
function getSystemStats()
{
    $stats = [
        'cpu' => '0%',
        'ram' => '0%',
        'disk' => '0%'
    ];

    // CPU Load
    if (is_readable('/proc/loadavg')) {
        $load = file_get_contents('/proc/loadavg');
        $load = explode(' ', $load);
        $stats['cpu'] = round(($load[0] * 100), 1) . '%';
    }

    // RAM usage
    if (is_readable('/proc/meminfo')) {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $totalMatches);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $availMatches);
        if ($totalMatches && $availMatches) {
            $total = $totalMatches[1];
            $available = $availMatches[1];
            $used = $total - $available;
            $stats['ram'] = round(($used / $total) * 100, 1) . '%';
        }
    }

    // Disk usage
    $totalDisk = disk_total_space("/");
    $freeDisk = disk_free_space("/");
    if ($totalDisk > 0) {
        $usedDisk = $totalDisk - $freeDisk;
        $stats['disk'] = round(($usedDisk / $totalDisk) * 100, 1) . '%';
    }

    return $stats;
}

$systemStats = getSystemStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-main: #f9fafb;
            --sidebar-bg: #111827;
            --card-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            display: flex; 
            height: 100vh; 
            background-color: var(--bg-main);
            color: var(--text-main);
        }

        /* Sidebar */
        .sidebar { 
            width: 260px; 
            background-color: var(--sidebar-bg); 
            color: white; 
            display: flex; 
            flex-direction: column;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar-header { 
            padding: 2rem 1.5rem; 
            border-bottom: 1px solid #374151;
            text-align: center;
        }
        .sidebar-header h3 { margin: 0; font-weight: 700; letter-spacing: -0.5px; }
        
        .nav-links { list-style: none; padding: 1rem 0; margin: 0; flex: 1; }
        .nav-links li a { 
            display: flex; 
            align-items: center;
            padding: 0.875rem 1.5rem; 
            color: #9ca3af; 
            text-decoration: none; 
            transition: all 0.2s;
            font-weight: 500;
        }
        .nav-links li a:hover, .nav-links li a.active { 
            background-color: #1f2937; 
            color: white; 
        }

        /* Main Content */
        .main-content { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2.5rem; 
        }
        .header h1 { margin: 0; font-size: 1.875rem; font-weight: 700; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { 
            padding: 0.5rem 1rem; 
            background-color: #ef4444; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            font-size: 0.875rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .logout-btn:hover { background-color: #dc2626; }

        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2.5rem;
        }
        .stat-card { 
            background: var(--card-bg); 
            padding: 1.5rem; 
            border-radius: 12px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .stat-card .label { font-size: 0.875rem; color: var(--text-muted); font-weight: 500; }
        .stat-card .value { font-size: 1.5rem; font-weight: 700; color: var(--text-main); }
        .stat-card .trend { font-size: 0.75rem; color: #10b981; font-weight: 600; }

        /* Progress Bar for Metrics */
        .metric-bar { 
            height: 6px; 
            background: #f3f4f6; 
            border-radius: 3px; 
            overflow: hidden; 
            margin-top: 0.5rem;
        }
        .metric-fill { height: 100%; background: var(--primary); transition: width 0.5s ease-out; }

        /* Content Sections */
        .grid-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        .card { 
            background: var(--card-bg); 
            padding: 1.5rem; 
            border-radius: 12px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }
        .card h2 { margin-top: 0; font-size: 1.25rem; margin-bottom: 1.25rem; font-weight: 600; }

        /* Recent Projects List */
        .activity-list { list-style: none; padding: 0; margin: 0; }
        .activity-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-info h4 { margin: 0; font-size: 0.9375rem; }
        .activity-info p { margin: 0; font-size: 0.8125rem; color: var(--text-muted); }
        .status-badge { 
            padding: 0.25rem 0.625rem; 
            border-radius: 9999px; 
            font-size: 0.75rem; 
            font-weight: 600;
            background: #ecfdf5;
            color: #059669;
        }

        /* Quick Actions */
        .quick-actions { display: grid; grid-template-columns: 1fr; gap: 0.75rem; }
        .action-btn { 
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem;
            background: #f9fafb;
            border: 1px solid var(--border);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-main);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .action-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Platform</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="/projects.php">Projects</a></li>
            <li><a href="/account.php">Account Settings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-menu">
                <span class="text-muted">Hello, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <!-- System Health Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="label">CPU Usage</span>
                <span class="value"><?php echo $systemStats['cpu']; ?></span>
                <div class="metric-bar">
                    <div class="metric-fill" style="width: <?php echo $systemStats['cpu']; ?>; background: #4f46e5;"></div>
                </div>
            </div>
            <div class="stat-card">
                <span class="label">Memory (RAM)</span>
                <span class="value"><?php echo $systemStats['ram']; ?></span>
                <div class="metric-bar">
                    <div class="metric-fill" style="width: <?php echo $systemStats['ram']; ?>; background: #10b981;"></div>
                </div>
            </div>
            <div class="stat-card">
                <span class="label">Disk Usage</span>
                <span class="value"><?php echo $systemStats['disk']; ?></span>
                <div class="metric-bar">
                    <div class="metric-fill" style="width: <?php echo $systemStats['disk']; ?>; background: #f59e0b;"></div>
                </div>
            </div>
            <div class="stat-card">
                <span class="label">Total Projects</span>
                <span class="value"><?php echo $projectStats['total']; ?></span>
                <span class="trend"><?php echo $projectStats['active']; ?> Active</span>
            </div>
        </div>

        <div class="grid-sections">
            <!-- Recent Projects -->
            <div class="card">
                <h2>Recent Projects</h2>
                <div class="activity-list">
                    <?php if (empty($projectStats['latest'])): ?>
                        <p class="text-muted">No projects found. Create one to get started.</p>
                    <?php
else: ?>
                        <?php foreach ($projectStats['latest'] as $project): ?>
                            <div class="activity-item">
                                <div class="activity-info">
                                    <h4><?php echo htmlspecialchars($project['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($project['slug']); ?>.localhost</p>
                                </div>
                                <span class="status-badge">Active</span>
                            </div>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </div>
                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="/projects.php" style="color: var(--primary); font-size: 0.875rem; font-weight: 600; text-decoration: none;">View All Projects →</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2>Quick Actions</h2>
                <div class="quick-actions">
                    <a href="/projects.php" class="action-btn">
                        <span>🚀</span> Manage Projects
                    </a>
                    <a href="/account.php" class="action-btn">
                        <span>⚙️</span> Account Settings
                    </a>
                    <a href="/public/api/public/projects.php" class="action-btn">
                        <span>📡</span> API Explorer
                    </a>
                    <a href="https://github.com" target="_blank" class="action-btn">
                        <span>📖</span> Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
