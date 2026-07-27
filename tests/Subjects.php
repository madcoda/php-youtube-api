<?php

namespace Madcoda\Youtube\Tests;

/**
 * The real Youtube resources the test suite refers to.
 *
 * Centralised so that when Google removes something, one file needs editing
 * rather than a dozen scattered string literals. Both the offline fixtures and
 * the live suite use these, which is what lets the fixture-drift test compare
 * like with like.
 *
 * Everything here was chosen for durability and verified against the live API
 * on 2026-07-27. The previous suite used whatever was to hand in 2013, and by
 * now one of its videos has been deleted outright while two others turned out
 * to live on unofficial re-upload channels that could vanish at any time.
 *
 * Rules of thumb when replacing any of these:
 *
 *   - Prefer a channel run by an institution over one run by a person.
 *   - Prefer a video that is historically significant, or is the official
 *     upload by the rights holder, over a copy.
 *   - Prefer the uploads playlist (UU...) over a curated one. It exists for
 *     exactly as long as the channel does and cannot be deleted separately.
 */
final class Subjects
{
    /**
     * "Me at the zoo", uploaded 2005-04-24: the first video ever uploaded to
     * Youtube. Nothing on the platform is less likely to be removed.
     */
    const VIDEO = 'jNQXAC9IVRw';

    /**
     * Rick Astley, "Never Gonna Give You Up", on the official artist channel.
     * Used as the second id wherever a multi-video response is needed.
     */
    const VIDEO_SECOND = 'dQw4w9WgXcQ';

    /**
     * "Android Demo", 2007, on the official Android Developers channel.
     */
    const VIDEO_THIRD = '1FJHYqE0RDg';

    /**
     * A video id that parses correctly but does not resolve, for exercising the
     * "no items in the response" path. This one was used by the suite for years
     * and has since been removed, which is precisely what makes it useful here.
     */
    const VIDEO_UNAVAILABLE = '_vA5PvDfNTs';

    /**
     * Youtube's own channel. If this disappears the API has bigger problems.
     */
    const CHANNEL = 'UCBR8-60-B28hp2BmDPdntcQ';

    /**
     * NASA. A public institution with a deep, continuously updated catalogue,
     * which makes it a safe subject for playlist, activity and search tests.
     */
    const CHANNEL_SECOND = 'UCLA_DiR1FfKNvjuUpBHmylQ';

    /**
     * NASA's uploads playlist. Every channel has one, it carries the channel id
     * with the "UC" prefix swapped for "UU", and it cannot be deleted while the
     * channel exists.
     */
    const PLAYLIST = 'UULA_DiR1FfKNvjuUpBHmylQ';

    /**
     * A search term with plenty of results on CHANNEL_SECOND.
     */
    const SEARCH_QUERY = 'Artemis';

    /**
     * A query chosen to match nothing, for the empty-result path.
     */
    const SEARCH_QUERY_NO_RESULTS = 'zzqxjv-no-such-video-zzqxjv-42781';

    /**
     * Handles, for URL-parsing tests. Youtube replaced legacy usernames with
     * these; getChannelByName()/forUsername no longer resolves for most
     * channels, which is why the live suite does not exercise it.
     */
    const CHANNEL_HANDLE = '@youtube';
}
