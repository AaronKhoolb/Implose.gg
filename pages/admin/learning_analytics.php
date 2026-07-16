<!--
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /pages/admin/learning_analytics.php
Description: Admin Learning Analytics Dashboard
            - System-wide overview stat cards
            - Topic accuracy column chart (all users)
            - Behavior distribution doughnut chart
            - System accuracy trend line chart
            - User performance table with search, accuracy bars, behavior badges
First Written on: Tuesday, 24-Jun-2026
Edited on: Wednesday, 02-Jul-2026
-->

<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/analytics_engine.php'); ?>

    <!-- Learning Analytics CSS -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_learning_analytics.css">

    <!-- import Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <title>Learning Analytics — Implose.gg Admin</title>
    <meta name="description" content="System-wide learning analytics dashboard for monitoring student performance.">
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_learning_analytics';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <?php
            // Fetch all system-wide analytics
            $sys_stats         = getSystemWideAnalytics($conn);
            $sys_topics        = getSystemTopicAccuracy($conn);
            $sys_behavior      = getSystemBehaviorPatterns($conn);
            $sys_progress      = getSystemProgressOverTime($conn);
            $user_performances = getUserPerformanceSummary($conn);

            $has_data = $sys_stats['total_attempts'] > 0;

            // Get per-user top weak topic for the table
            $user_weak_map     = array();
            $user_behavior_map = array();
            foreach ($user_performances as $up) {
                $uid_topics = getTopicAccuracy($conn, $up['user_id']);
                $weakest = null;
                foreach ($uid_topics as $t) {
                    if ($t['accuracy_pct'] < 50) {
                        $weakest = $t['topic_tag'];
                        break; 
                    }
                }
                $user_weak_map[$up['user_id']] = $weakest;

                // Also get dominant behavior
                $uid_beh  = getBehaviorPatterns($conn, $up['user_id']);
                $dominant = 'proficient';
                $max_v    = $uid_beh['proficient'];
                if ($uid_beh['rushing'] > $max_v) {
                    $dominant = 'rushing';
                    $max_v    = $uid_beh['rushing'];
                }
                if ($uid_beh['struggling'] > $max_v) {
                    $dominant = 'struggling';
                }
                $user_behavior_map[$up['user_id']] = $dominant;
            }
        ?>


        <!-- Page Header -->
        <div class="la-page-header">
            <div class="la-page-header-left">
                <h1>Learning Analytics</h1>
                <p>Monitor system-wide learning performance, behavior patterns, and topic insights.</p>
            </div>
        </div>


        <?php if (!$has_data): ?>

            <!-- Empty State -->
            <div class="la-empty-state">
                <span class="la-empty-icon">📊</span>
                <h2 class="la-empty-title">No Analytics Data Yet</h2>
                <p class="la-empty-text">
                    No quiz attempts have been recorded in the system. Analytics will appear here once users begin taking quizzes.
                </p>
            </div>

        <?php else: ?>


           <!-- System Overview Stats -->
            <div class="la-stats-row">

                <div class="la-stat-card">
                    <span class="la-stat-label">Total Quiz Attempts</span>
                    <span class="la-stat-value"><?php echo number_format($sys_stats['total_attempts']); ?></span>
                    <div class="la-stat-icon">
                        <img src="/Implose.gg-src/assets/images/icons/stats.svg" alt="">
                    </div>
                </div>

                <div class="la-stat-card">
                    <span class="la-stat-label">Average Accuracy</span>
                    <span class="la-stat-value">
                        <?php echo $sys_stats['avg_accuracy']; ?><span class="la-stat-unit">%</span>
                    </span>
                    <div class="la-stat-icon">
                        <img src="/Implose.gg-src/assets/images/icons/activity.svg" alt="">
                    </div>
                </div>

                <div class="la-stat-card">
                    <span class="la-stat-label">Active Learners</span>
                    <span class="la-stat-value"><?php echo $sys_stats['active_learners']; ?></span>
                    <div class="la-stat-icon">
                        <img src="/Implose.gg-src/assets/images/icons/users.svg" alt="">
                    </div>
                </div>

                <div class="la-stat-card">
                    <span class="la-stat-label">Most Weak Topic</span>
                    <span class="la-stat-value" style="font-size: 22px; word-break: break-word;">
                        <?php echo htmlspecialchars($sys_stats['most_weak_topic']); ?>
                    </span>
                    <div class="la-stat-icon">
                        <img src="/Implose.gg-src/assets/images/icons/alert.svg" alt="">
                    </div>
                </div>

            </div>


            <!-- Charts Row (Topic Accuracy + Behavior) -->
            <div class="la-charts-row">

                <!-- System-Wide Topic Performance -->
                <div class="la-chart-card">
                    <h3>Topic Accuracy (System-Wide)</h3>
                    <div class="la-chart-container">
                        <canvas id="sysTopicChart"></canvas>
                    </div>
                </div>

                <!-- System Behavior Distribution -->
                <div class="la-chart-card">
                    <h3>Behavior Distribution</h3>
                    <div class="la-chart-container">
                        <canvas id="sysBehaviorChart"></canvas>
                    </div>
                </div>

            </div>


            <!-- System Accuracy Trend -->
            <div class="la-progress-section">
                <div class="la-progress-card">
                    <h3>System Accuracy Trend (Last 30 Days)</h3>
                    <div class="la-progress-container">
                        <canvas id="sysProgressChart"></canvas>
                    </div>
                </div>
            </div>


            <!-- User Performance Table -->
            <div class="la-table-section">
                <div class="la-table-card">

                    <div class="la-toolbar">
                        <h3>User Performance</h3>
                        <div class="la-search-wrap">
                            <input type="text"
                                   class="la-search"
                                   id="la-search-input"
                                   placeholder="Search users..."
                                   oninput="filterUserTable()">
                            <img class="search-icon" src="/Implose.gg-src/assets/images/icons/search.svg" alt="Search">
                        </div>
                    </div>

                    <div class="la-table-wrap">
                        <?php if (empty($user_performances)): ?>
                            <div class="la-table-empty">No user performance data available.</div>
                        <?php else: ?>
                            <table class="la-perf-table" id="la-perf-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Questions</th>
                                        <th>Accuracy</th>
                                        <th>Avg Response</th>
                                        <th>Top Weak Topic</th>
                                        <th>Behavior</th>
                                        <th>Last Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_performances as $up):
                                        $acc = $up['accuracy_pct'];
                                        $acc_class = $acc >= 80 ? 'high' : ($acc >= 50 ? 'mid' : 'low');
                                        $weak_tag = $user_weak_map[$up['user_id']] ?? '—';
                                        $dom_beh  = $user_behavior_map[$up['user_id']] ?? 'proficient';
                                        $initials = strtoupper(substr($up['username'], 0, 2));
                                    ?>
                                    <tr class="la-user-row" data-username="<?php echo strtolower(htmlspecialchars($up['username'])); ?>">
                                        <td>
                                            <div class="la-user-cell">
                                                <div class="la-user-avatar">
                                                    <?php if (!empty($up['avatar_path'])): ?>
                                                        <img src="/Implose.gg-src/<?php echo htmlspecialchars($up['avatar_path']); ?>" alt="">
                                                    <?php else: ?>
                                                        <?php echo $initials; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="la-user-name"><?php echo htmlspecialchars($up['username']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $up['total_answered']; ?></td>
                                        <td>
                                            <div class="la-accuracy-cell">
                                                <div class="la-accuracy-bar">
                                                    <div class="la-accuracy-fill <?php echo $acc_class; ?>" style="width: <?php echo $acc; ?>%"></div>
                                                </div>
                                                <span class="la-accuracy-text <?php echo $acc_class; ?>"><?php echo $acc; ?>%</span>
                                            </div>
                                        </td>
                                        <td><?php echo $up['avg_response_time']; ?>s</td>
                                        <td>
                                            <?php if ($weak_tag !== '—'): ?>
                                                <span class="la-topic-badge"><?php echo htmlspecialchars($weak_tag); ?></span>
                                            <?php else: ?>
                                                <span style="color: var(--admin-text-dim);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="la-behavior-badge <?php echo $dom_beh; ?>">
                                                <?php echo ucfirst($dom_beh); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $up['last_activity'] ? date('j M Y', strtotime($up['last_activity'])) : '—'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                </div>
            </div>


        <?php endif; ?>

    </div>


    <?php if ($has_data): ?>
    <script>
    // Chart.js Global Defaults
    Chart.defaults.color = '#859397';
    Chart.defaults.font.family = "'Inter', -apple-system, system-ui, sans-serif";
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;


    // CHART 1: System Topic Performance (Bar)
    (function() {
        const topicData = <?php echo json_encode($sys_topics); ?>;
        const labels = topicData.map(function(t) { return t.topic_tag; });
        const values = topicData.map(function(t) { return t.accuracy_pct; });
        const colors = values.map(function(v) {
            if (v >= 80) return 'rgba(74, 222, 128, 0.75)';
            if (v >= 50) return 'rgba(245, 158, 11, 0.75)';
            return 'rgba(239, 68, 68, 0.75)';
        });
        const borderColors = values.map(function(v) {
            if (v >= 80) return '#4ADE80';
            if (v >= 50) return '#f59e0b';
            return '#ef4444';
        });

        new Chart(document.getElementById('sysTopicChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Accuracy %',
                    data: values,
                    backgroundColor: colors,
                    hoverBackgroundColor: colors,
                    borderColor: borderColors,
                    hoverBorderColor: borderColors,
                    borderWidth: 1.5,
                    borderRadius: 6,
                    maxBarThickness: 52
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                animations: { colors: false, numbers: false },
                transitions: { active: { animation: { duration: 0 } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { callback: function(v) { return v + '%'; } }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) { return ctx.parsed.y + '% accuracy'; }
                        }
                    }
                }
            }
        });
    })();


    // CHART 2: System Behavior Distribution (Doughnut)
    (function() {
        const beh = <?php echo json_encode($sys_behavior); ?>;

        new Chart(document.getElementById('sysBehaviorChart'), {
            type: 'doughnut',
            data: {
                labels: ['Proficient', 'Rushing', 'Struggling'],
                datasets: [{
                    data: [beh.proficient, beh.rushing, beh.struggling],
                    backgroundColor: [
                        'rgba(74, 222, 128, 0.75)',
                        'rgba(239, 68, 68, 0.75)',
                        'rgba(245, 158, 11, 0.75)'
                    ],
                    hoverBackgroundColor: [
                        'rgba(74, 222, 128, 0.75)',
                        'rgba(239, 68, 68, 0.75)',
                        'rgba(245, 158, 11, 0.75)'
                    ],
                    borderColor: ['#4ADE80', '#ef4444', '#f59e0b'],
                    hoverBorderColor: ['#4ADE80', '#ef4444', '#f59e0b'],
                    borderWidth: 1.5,
                    hoverOffset: 0,
                    hoverBorderWidth: 1.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                animations: { colors: false, numbers: false },
                transitions: { active: { animation: { duration: 0 } } },
                cutout: '55%',
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                const pct = total > 0 ? Math.round(ctx.raw / total * 100) : 0;
                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    })();


    // CHART 3: System Accuracy Trend (Line)
    (function() {
        const progressData = <?php echo json_encode($sys_progress); ?>;
        const labels = progressData.map(function(p) {
            var d = new Date(p.date);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const values = progressData.map(function(p) { return p.accuracy_pct; });

        new Chart(document.getElementById('sysProgressChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Accuracy %',
                    data: values,
                    borderColor: '#22d3ee',
                    backgroundColor: 'rgba(34, 211, 238, 0.06)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#22d3ee',
                    pointBorderColor: '#111827',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                animations: { colors: false, numbers: false },
                transitions: { active: { animation: { duration: 0 } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { callback: function(v) { return v + '%'; } }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) { return ctx.parsed.y + '% accuracy'; }
                        }
                    }
                }
            }
        });
    })();


    // Table Search Filter
    function filterUserTable() {
        const query = document.getElementById('la-search-input').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.la-user-row');

        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var username = row.getAttribute('data-username') || '';
            row.style.display = username.indexOf(query) !== -1 ? '' : 'none';
        }
    }
    </script>
    <?php endif; ?>

</body>
</html>
