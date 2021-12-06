<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Process Zoom webhook "All Recordings have completed".
 *
 * For more information visit:
 * https://marketplace.zoom.us/docs/api-reference/webhook-reference/recording-events/recording-completed
 *
 * @package   block_uploadvimeo
 * @copyright 2021 CCEAD PUC-Rio (@angela-araujo)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require ('../../config.php');

$notification = json_decode(
'{
  "event": "recording.completed",
  "event_ts": 1626230691572,
  "payload": {
    "account_id": "AAAAAABBBB",
    "object": {
      "id": 1234567890,
      "uuid": "4444AAAiAAAAAiAiAiiAii==",
      "host_id": "x1yCzABCDEfg23HiJKl4mN",
      "account_id": "x1yCzABCDEfg23HiJKl4mN",
      "topic": "My Personal Recording",
      "type": 4,
      "start_time": "2021-07-13T21:44:51Z",
      "timezone": "America/Los_Angeles",
      "host_email": "user@example.com",
      "duration": 60,
      "password": "132456",
      "share_url": "https://example.com",
      "total_size": 3328371,
      "recording_count": 2,
      "thumbnail_links": [
        "https://example.com/replay/2021/07/25/123456789/E54E639G-37B1-4E1G-0D17-3BAA548DD0CF/GMT20210725-123456_Recording_gallery_widthxheight_tb_width1xheight1.jpg"
      ],
      "recording_files": [
        {
          "id": "ed6c2f27-2ae7-42f4-b3d0-835b493e4fa8",
          "meeting_id": "098765ABCD",
          "recording_start": "2021-03-23T22:14:57Z",
          "recording_end": "2021-03-23T23:15:41Z",
          "file_type": "M4A",
          "file_size": 246560,
          "file_extension": "M4A",
          "play_url": "https://example.com/recording/play/Qg75t7xZBtEbAkjdlgbfdngBBBB",
          "download_url": "https://example.com/recording/download/Qg75t7xZBtEbAkjdlgbfdngBBBB",
          "status": "completed",
          "recording_type": "audio_only"
        },
        {
          "id": "388ffb46-1541-460d-8447-4624451a1db7",
          "meeting_id": "098765ABCD",
          "recording_start": "2021-03-23T22:14:57Z",
          "recording_end": "2021-03-23T23:15:41Z",
          "file_type": "MP4",
          "file_size": 282825,
          "file_extension": "MP4",
          "play_url": "https://example.com/recording/play/Qg75t7xZBtEbAkjdlgbfdngCCCC",
          "download_url": "https://example.com/recording/download/Qg75t7xZBtEbAkjdlgbfdngCCCC",
          "status": "completed",
          "recording_type": "shared_screen_with_speaker_view"
        }
      ],
      "participant_audio_files": [
        {
          "id": "ed6c2f27-2ae7-42f4-b3d0-835b493e4fa8",
          "recording_start": "2021-03-23T22:14:57Z",
          "recording_end": "2021-03-23T23:15:41Z",
          "recording_type": "audio_only",
          "file_type": "M4A",
          "file_name": "MyRecording",
          "file_size": 246560,
          "file_extension": "MP4",
          "play_url": "https://example.com/recording/play/Qg75t7xZBtEbAkjdlgbfdngAAAA",
          "download_url": "https://example.com/recording/download/Qg75t7xZBtEbAkjdlgbfdngAAAA",
          "status": "completed"
        }
      ]
    }
  },
  "download_token": "abJhbGciOiJIUzUxMiJ9.eyJpc3MiOiJodHRwczovL2V2ZW50Lnpvb20udXMiLCJhY2NvdW50SWQiOiJNdDZzdjR1MFRBeVBrd2dzTDJseGlBIiwiYXVkIjoiaHR0cHM6Ly9vYXV0aC56b29tLnVzIiwibWlkIjoieFp3SEc0c3BRU2VuekdZWG16dnpiUT09IiwiZXhwIjoxNjI2MTM5NTA3LCJ1c2VySWQiOiJEWUhyZHBqclMzdWFPZjdkUGtrZzh3In0.a6KetiC6BlkDhf1dP4KBGUE1bb2brMeraoD45yhFx0eSSSTFdkHQnsKmlJQ-hdo9Zy-4vQw3rOxlyoHv583JyZ"
}'
);

block_uploadvimeo\local\uploadvimeo::process_zoom_webhook($notification->payload);
