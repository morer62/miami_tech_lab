<?php
use App\Repositories\MiamiTechShowsRepository;
$route = trim((string)($GLOBALS['miami_tech_route'] ?? ''), '/');
$section = explode('/', $route)[0] ?: 'home';
$serviceSlug = $section === 'services' ? (explode('/', $route)[1] ?? '') : '';
$pages = [
 'shows'=>['Shows','Ideas worth operating.','Original conversations with builders, operators and technical leaders shaping Miami.',['Local Tech Lab','Ground Floor','Episodes','Guests']],
 'events'=>['Events','Meet the people building what is next.','Networking, workshops, founder meetups and practical AI sessions across South Florida.',['Upcoming','Calendar','Networking','Workshops']],
 'software'=>['Software','Useful tools, not demos.','Launch focused applications built for business operators, marketers, developers and event teams.',['AI Proposal Builder','SEO Audit Tool','Event Budget Calculator','Content Planner']],
 'community'=>['Community','A serious room for ambitious builders.','Create a profile, meet members, share opportunities and gain access to private sessions and tools.',['Members','Companies','Discussions','Opportunities']],
 'learn'=>['Learn','Practical knowledge for Monday morning.','Courses, tutorials and workshops on AI, software, security, analytics, automation and growth.',['Courses','Tutorials','Workshops','Guides']],
 'resources'=>['Resources','The operating library.','Templates, prompts, checklists, reports and calculators made to be used—not merely saved.',['Templates','Prompts','Calculators','Reports']],
 'news'=>['News','What Tech Lab Miami is shipping.','Announcements, product releases, member stories, partnerships and event recaps.',['Announcements','Software Releases','Community','Event Recaps']],
 'benefits'=>['Benefits','Membership that creates leverage.','Private offers, studio access, software licenses, event tickets and partner advantages.',['Member Perks','Partner Offers','Private Access','Software Access']],
 'partners'=>['Partners','Build the ecosystem with us.','Technology companies, venues, educators and local operators can sponsor, contribute and create programs.',['Technology','Venues','Education','Community']],
 'speakers'=>['Speakers','People with something real to teach.','Discover past guests or apply to lead an honest, practical conversation.',['AI','Engineering','Operations','Growth']],
 'people'=>['People','Built by operators, not influencers.','Jonathan Moreno brings product, project and operations leadership. Lucas Alvarado brings engineering, martech, data and infrastructure depth.',['Jonathan Moreno','Lucas Alvarado','Team','Advisors']],
 'about'=>['About','Why Tech Lab Miami exists.','Miami needs a durable bridge between technology, entrepreneurship, education and real business execution.',['Mission','Story','Roadmap','Contact']],
 'impact'=>['Impact','A public record of useful work.','See the programs, releases, conversations and community outcomes Tech Lab Miami can verify.',['Programs','Releases','Community Outcomes','Annual Reports']],
 'press'=>['Press','News, context and media resources.','Verified announcements, coverage and brand resources for journalists and community partners.',['Newsroom','Media Kit','Coverage','Contact']],
 'speaking'=>['Speaking','Practical voices for useful rooms.','Invite Tech Lab Miami operators and guests to discuss AI, software, operations and South Florida innovation.',['Topics','Formats','Past Sessions','Request']],
 'guests'=>['Guests','Meet the people behind the conversations.','Builders, operators and technical leaders who have shared practical experience through Tech Lab Miami shows.',['Operators','Founders','Engineers','Community Leaders']],
 'south-florida'=>['South Florida','Technology built in regional context.','Programs, conversations and resources connecting builders across Broward, Miami-Dade and Palm Beach.',['Sunrise','Broward','Miami-Dade','Palm Beach']],
 'studio'=>['Studio','Where the conversations are produced.','The Tech Lab Miami studio in Sunrise supports recordings, practical sessions and community storytelling.',['Recordings','Production','Visits','Partnerships']],
 'now'=>['Now','Everything happening next.','One live command center for the next show, meetup, workshop, software release and announcement.',['Next Show','Next Meetup','Next Workshop','Newest Release']],
 'directory'=>['Miami Tech Directory','Find trusted local capability.','Discover verified developers, founders, agencies, AI consultants, cybersecurity teams and creative operators.',['Developers','Startups','AI Consultants','Agencies']],
 'membership'=>['Membership','Start free. Grow with the network.','Community, private, premium and partner access levels keep the right opportunities available to the right people.',['Community Member','Private Member','Premium Member','Partner']],
 'media'=>['Media','Miami builders in motion.','Shows, event galleries, speaker stories, community moments and behind-the-scenes work.',['Shows','Events','Speakers','Behind the Scenes']],
];
$routePages = [
 'about/mission'=>['Mission','Make technology useful in public.','Tech Lab Miami connects education, original media, practical software and community programs so South Florida builders can make better technology decisions.',['Education','Media','Software','Community']],
 'about/team'=>['Team','Operators building the platform.','Meet the people responsible for Tech Lab Miami’s programs, software, editorial work and community operations.',['Leadership','Engineering','Editorial','Community']],
 'about/editorial-policy'=>['Editorial Policy','How our public work earns trust.','We distinguish reporting, analysis and Tech Lab Miami projects; verify claims; disclose relationships; and correct material errors.',['Accuracy','Independence','Disclosures','Corrections']],
 'impact/reports'=>['Impact Reports','Verified activity, published clearly.','This archive documents programs, releases and community outcomes only when the supporting evidence can be published responsibly.',['Programs','Software','Media','Community']],
 'press/media-kit'=>['Media Kit','Accurate context for coverage.','Find approved organization context, public links and contact information for coverage of Tech Lab Miami.',['Organization','Brand','Founders','Contact']],
 'speaking/topics'=>['Speaking Topics','Practical sessions for working teams.','Explore talks and workshops about applied AI, automation, software operations and technology communities in South Florida.',['Applied AI','Automation','Operations','Community']],
 'events/upcoming'=>['Upcoming Events','Find the next useful room.','Confirmed Tech Lab Miami meetups, workshops, recordings and community sessions appear here with current access details.',['Meetups','Workshops','Recordings','Community']],
 'events/past'=>['Past Events','A record of completed programs.','Browse verified recaps and resources after Tech Lab Miami events take place and the public record is ready.',['Recaps','Speakers','Resources','Field Notes']],
 'community/directory'=>['Community Directory','Discover verified local capability.','Find public profiles for South Florida builders and organizations that choose to participate in the directory.',['People','Companies','Expertise','South Florida']],
 'community/foundations'=>['Community Foundations','The rules behind a useful network.','Tech Lab Miami is built around accurate profiles, respectful participation, practical contribution and transparent access.',['Trust','Contribution','Access','Accountability']],
 'south-florida/sunrise'=>['Technology Community in Sunrise','Built from a Broward operating base.','Tech Lab Miami operates from Sunrise and connects builders through practical education, original conversations and regional programs.',['Shows','Events','Software','Community']],
 'south-florida/fort-lauderdale'=>['Technology Community in Fort Lauderdale','Useful connections across central Broward.','Explore verified programs, people and technology resources relevant to the Fort Lauderdale business community.',['Programs','People','Insights','Resources']],
 'south-florida/miami'=>['Technology Community in Miami','Connecting builders across Miami-Dade.','Explore verified conversations, programs and resources relevant to founders and operators in Miami-Dade.',['Shows','Events','People','Insights']],
 'south-florida/west-palm-beach'=>['Technology Community in West Palm Beach','A regional bridge to Palm Beach County.','Explore verified programs and technology resources relevant to builders and operators in Palm Beach County.',['Programs','People','Insights','Resources']],
];
$page = $routePages[$route] ?? ($pages[$section] ?? ['Tech Lab Miami','Miami’s operating system for technology and business.','Shows, events, software and a serious community for people who build.',['Shows','Events','Software','Community']]);
if ($serviceSlug === 'ai-consulting') {
    $page = ['AI Consulting', 'Use AI where it creates measurable leverage.', 'Practical guidance for South Florida businesses evaluating AI for customer service, operations, knowledge work, marketing and decision support.', ['Opportunity Assessment', 'Workflow Design', 'Responsible Adoption', 'Team Enablement']];
} elseif ($serviceSlug === 'business-automation') {
    $page = ['Business Automation', 'Remove repetitive work without adding operational chaos.', 'We help small businesses map workflows, connect systems and automate the right steps while preserving human judgment and accountability.', ['Workflow Audit', 'System Integration', 'Automation Roadmap', 'Measurement']];
}
$shows = [];
$show = false;
$contentWarning = null;
$homeSignal = false;
if ($section === 'home') {
    try { $homeSignal = (new MiamiTechShowsRepository())->homeSignal(); }
    catch (Throwable $e) { error_log('Tech Lab Miami home show signal is unavailable: ' . $e->getMessage()); }
}
if ($section === 'shows') {
    try {
        $repository = new MiamiTechShowsRepository();
        $parts = explode('/', $route);
        if (!empty($parts[1])) {
            $show = $repository->publishedShow($parts[1]);
            if ($show) {
                $page = [$show->title, $show->title, $show->tagline ?: $show->description, []];
            } else {
                http_response_code(404);
            }
        } else {
            $shows = $repository->publishedShows();
        }
    } catch (Throwable $e) {
        error_log('Tech Lab Miami shows are unavailable: ' . $e->getMessage());
        $contentWarning = 'The show library is being prepared. Check back soon.';
    }
}
return compact('route','section','page','pages','shows','show','homeSignal','contentWarning');
