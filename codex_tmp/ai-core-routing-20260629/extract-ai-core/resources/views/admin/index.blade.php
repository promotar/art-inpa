@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page__header">
        <div>
            <h1>AI Core</h1>
            <p>Central AI gateway, model registry, tool registry, datasets, permissions, and audit layer.</p>
        </div>
    </div>

    <div class="admin-card">
        <h2>Gateway</h2>
        <dl class="ai-core-grid">
            <div>
                <dt>Base URL</dt>
                <dd>{{ $settings['gateway_base_url'] ?? 'Not configured' }}</dd>
            </div>
            <div>
                <dt>API Key</dt>
                <dd>{{ $settings['gateway_api_key'] ?? 'missing' }}</dd>
            </div>
            <div>
                <dt>Default Timeout</dt>
                <dd>{{ $settings['default_timeout'] ?? 60 }} seconds</dd>
            </div>
            <div>
                <dt>Image Timeout</dt>
                <dd>{{ $settings['image_timeout'] ?? 300 }} seconds</dd>
            </div>
        </dl>
    </div>

    <div class="admin-card">
        <h2>Models</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Backend</th>
                        <th>Endpoint</th>
                        <th>Risk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($models as $model)
                        <tr>
                            <td>{{ $model['slug'] }}</td>
                            <td>{{ $model['type'] }}</td>
                            <td>{{ $model['backend'] }}</td>
                            <td><code>{{ $model['endpoint'] }}</code></td>
                            <td>{{ $model['risk_level'] }}</td>
                            <td>{{ $model['enabled'] ? 'Enabled' : 'Disabled' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <h2>Tools</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Endpoint</th>
                        <th>Permission</th>
                        <th>Risk</th>
                        <th>Approval</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tools as $tool)
                        <tr>
                            <td>{{ $tool['slug'] }}</td>
                            <td><code>{{ $tool['endpoint'] }}</code></td>
                            <td>{{ $tool['required_permission'] ?? '-' }}</td>
                            <td>{{ $tool['risk_level'] }}</td>
                            <td>{{ $tool['requires_approval'] ? 'Required' : 'No' }}</td>
                            <td>{{ $tool['enabled'] ? 'Enabled' : 'Disabled' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <h2>Datasets</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Owner Plugin</th>
                        <th>Source Type</th>
                        <th>Collection</th>
                        <th>Privacy</th>
                        <th>Indexing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datasets as $dataset)
                        <tr>
                            <td>{{ $dataset['slug'] }}</td>
                            <td>{{ $dataset['owner_plugin'] }}</td>
                            <td>{{ $dataset['source_type'] }}</td>
                            <td>{{ $dataset['rag_collection'] }}</td>
                            <td>{{ $dataset['privacy_level'] }}</td>
                            <td>{{ $dataset['indexing_status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <h2>Audit</h2>
        <dl class="ai-core-grid">
            <div>
                <dt>Requests</dt>
                <dd>{{ $requestCount }}</dd>
            </div>
            <div>
                <dt>Audit Events</dt>
                <dd>{{ $auditCount }}</dd>
            </div>
        </dl>
    </div>
</div>

<style>
    .ai-core-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        margin: 0;
    }

    .ai-core-grid div {
        border: 1px solid #d8dee6;
        border-radius: 6px;
        padding: 14px;
        background: #fff;
    }

    .ai-core-grid dt {
        color: #526071;
        font-size: 12px;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    .ai-core-grid dd {
        margin: 0;
        font-weight: 600;
        word-break: break-word;
    }
</style>
@endsection
