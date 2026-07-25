<?php

namespace App\Platform\Core\PageBuilder;

class SchemaFirstSavePublishService
{
    /**
     * @param array<string, mixed> $project
     * @return array<string, mixed>
     */
    public function normalizeProject(array $project): array
    {
        $project['schema_first'] = array_merge(
            is_array($project['schema_first'] ?? null) ? $project['schema_first'] : [],
            [
                'source_of_truth' => 'project_json',
                'html_css_role' => 'publish_output',
                'version' => 'schema-first/v1',
            ],
        );

        return $project;
    }
}
