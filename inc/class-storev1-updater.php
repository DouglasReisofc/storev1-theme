<?php
defined('ABSPATH') || exit;

final class StoreV1_Updater {
    const REPO = 'https://github.com/DouglasReisofc/storev1-theme';
    const API = 'https://api.github.com/repos/DouglasReisofc/storev1-theme/releases/latest';
    const SLUG = 'storev1-theme';

    public static function boot() {
        add_filter('update_themes_github.com', [__CLASS__, 'check'], 10, 4);
    }

    public static function check($update, $theme_data, $stylesheet, $locales) {
        if (self::SLUG !== $stylesheet) return $update;
        $release = get_transient('storev1_latest_release');
        $force = is_admin() && current_user_can('update_themes') && isset($_GET['force-check']);
        if (false === $release || $force) {
            $response = wp_remote_get(self::API, [
                'timeout' => 10,
                'headers' => ['Accept'=>'application/vnd.github+json','User-Agent'=>'StoreV1-Theme-Updater'],
            ]);
            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) return $update;
            $release = json_decode(wp_remote_retrieve_body($response), true);
            if (!is_array($release)) return $update;
            set_transient('storev1_latest_release', $release, HOUR_IN_SECONDS);
        }
        if (empty($release['tag_name']) || !empty($release['draft']) || !empty($release['prerelease'])) return $update;
        $version = ltrim($release['tag_name'], 'v');
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) return $update;
        foreach (isset($release['assets']) && is_array($release['assets']) ? $release['assets'] : [] as $asset) {
            $expected = self::REPO . '/releases/download/v' . $version . '/storev1-theme-' . $version . '.zip';
            if (isset($asset['name'], $asset['browser_download_url']) &&
                'storev1-theme-' . $version . '.zip' === $asset['name'] &&
                $expected === $asset['browser_download_url']) {
                return [
                    'id'=>self::REPO, 'theme'=>self::SLUG, 'version'=>$version,
                    'url'=>self::REPO . '/releases/tag/v' . $version,
                    'package'=>$expected, 'requires'=>'6.1', 'requires_php'=>'7.4',
                ];
            }
        }
        return $update;
    }
}
StoreV1_Updater::boot();
