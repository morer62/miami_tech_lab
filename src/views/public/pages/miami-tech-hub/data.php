<?php
$route = trim((string)($GLOBALS['miami_tech_route'] ?? ''), '/');
$section = explode('/', $route)[0] ?: 'home';
$pages = [
 'shows'=>['Shows','Ideas worth operating.','Original conversations with builders, operators and technical leaders shaping Miami.',['Local Tech Lab','Ground Floor','Episodes','Guests']],
 'events'=>['Events','Meet the people building what is next.','Networking, workshops, founder meetups and practical AI sessions across South Florida.',['Upcoming','Calendar','Networking','Workshops']],
 'software'=>['Software','Useful tools, not demos.','Launch focused applications built for business operators, marketers, developers and event teams.',['AI Proposal Builder','SEO Audit Tool','Event Budget Calculator','Content Planner']],
 'community'=>['Community','A serious room for ambitious builders.','Create a profile, meet members, share opportunities and gain access to private sessions and tools.',['Members','Companies','Discussions','Opportunities']],
 'learn'=>['Learn','Practical knowledge for Monday morning.','Courses, tutorials and workshops on AI, software, security, analytics, automation and growth.',['Courses','Tutorials','Workshops','Guides']],
 'resources'=>['Resources','The operating library.','Templates, prompts, checklists, reports and calculators made to be used—not merely saved.',['Templates','Prompts','Calculators','Reports']],
 'news'=>['News','What Miami Tech Lab is shipping.','Announcements, product releases, member stories, partnerships and event recaps.',['Announcements','Software Releases','Community','Event Recaps']],
 'benefits'=>['Benefits','Membership that creates leverage.','Private offers, studio access, software licenses, event tickets and partner advantages.',['Member Perks','Partner Offers','Private Access','Software Access']],
 'partners'=>['Partners','Build the ecosystem with us.','Technology companies, venues, educators and local operators can sponsor, contribute and create programs.',['Technology','Venues','Education','Community']],
 'speakers'=>['Speakers','People with something real to teach.','Discover past guests or apply to lead an honest, practical conversation.',['AI','Engineering','Operations','Growth']],
 'people'=>['People','Built by operators, not influencers.','Jonathan Moreno brings product, project and operations leadership. Lucas Alvarado brings engineering, martech, data and infrastructure depth.',['Jonathan Moreno','Lucas Alvarado','Team','Advisors']],
 'about'=>['About','Why Miami Tech Lab exists.','Miami needs a durable bridge between technology, entrepreneurship, education and real business execution.',['Mission','Story','Roadmap','Contact']],
 'now'=>['Now','Everything happening next.','One live command center for the next show, meetup, workshop, software release and announcement.',['Next Show','Next Meetup','Next Workshop','Newest Release']],
 'directory'=>['Miami Tech Directory','Find trusted local capability.','Discover verified developers, founders, agencies, AI consultants, cybersecurity teams and creative operators.',['Developers','Startups','AI Consultants','Agencies']],
 'membership'=>['Membership','Start free. Grow with the network.','Community, private, premium and partner access levels keep the right opportunities available to the right people.',['Community Member','Private Member','Premium Member','Partner']],
 'media'=>['Media','Miami builders in motion.','Shows, event galleries, speaker stories, community moments and behind-the-scenes work.',['Shows','Events','Speakers','Behind the Scenes']],
];
$page = $pages[$section] ?? ['Miami Tech Lab','Miami’s operating system for technology and business.','Shows, events, software and a serious community for people who build.',['Shows','Events','Software','Community']];
return compact('route','section','page','pages');
