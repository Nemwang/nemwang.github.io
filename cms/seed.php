<?php
// Starting content, copied from the original static pages. Loaded once by
// admin/install.php; after that everything is edited in the admin panel.

$wpServices = 'Digital Marketing ( SEO, Graphics Designing )';
$wpText = 'We created a wordpress website and hosted through hostinger server for this project.';

$gallery = [];
foreach ([
    'Artist Landing page', 'Educational institutional web design', 'Plant app Ui', 'Settings UI with UX',
    'Smart Home UI', 'User Profile UI design', 'calc', 'checkout', 'cycle', 'error', 'food ordering app',
    'gym', 'icon', 'landing', 'travel', 'user',
] as $name) {
    $gallery[] = ['name' => $name, 'image' => "images/gallery/$name.jpg"];
}

return [

    'menu' => [
        ['label' => 'Home', 'url' => '#page1', 'show_on' => 'home'],
        ['label' => 'Specialization', 'url' => '#page2', 'show_on' => 'home'],
        ['label' => 'Technical Skills', 'url' => '#page3', 'show_on' => 'home'],
        ['label' => 'Services', 'url' => '#page4', 'show_on' => 'home'],
        ['label' => 'Resume', 'url' => '#page5', 'show_on' => 'home'],
        ['label' => 'Projects', 'url' => 'projects.php', 'show_on' => 'home'],
        ['label' => 'Contact Me', 'url' => '#page6', 'show_on' => 'home'],
        ['label' => 'Home', 'url' => './', 'show_on' => 'inner'],
        ['label' => 'Projects', 'url' => 'projects.php', 'show_on' => 'ux'],
        ['label' => 'UI/UX Designs', 'url' => 'ux.php', 'show_on' => 'projects'],
    ],

    'social' => [
        ['network' => 'twitter', 'url' => 'https://twitter.com/suman_nemwang'],
        ['network' => 'facebook', 'url' => 'https://www.facebook.com/suman.nemwang'],
        ['network' => 'linkedin', 'url' => 'https://www.linkedin.com/in/j-nembang/'],
        ['network' => 'github', 'url' => 'https://github.com/Nemwang'],
        ['network' => 'dribbble', 'url' => 'https://dribbble.com/Nemwang'],
        ['network' => 'behance', 'url' => 'https://www.behance.net/yangdemba'],
    ],

    'progress' => [
        ['label' => 'Development', 'percent' => '60'],
        ['label' => 'WordPress', 'percent' => '90'],
        ['label' => 'Graphics Design', 'percent' => '80'],
        ['label' => 'Marketing', 'percent' => '60'],
        ['label' => 'UI/UX Designing', 'percent' => '80'],
    ],

    'skills' => [
        ['title' => 'Html & CSS', 'description' => 'For Frontend', 'link' => '', 'background' => 'images/bg/it.jpg'],
        ['title' => 'UI/UX Design', 'description' => "For Website & WebApp\nClick to view Projects", 'link' => 'ux.php', 'background' => 'images/bg/bg1.jpg'],
        ['title' => 'WordPress', 'description' => "Creating a complete Website\nClick to view Projects", 'link' => 'projects.php', 'background' => 'images/bg/bg.jpg'],
        ['title' => 'Django', 'description' => 'Backend', 'link' => '', 'background' => 'images/bg/it.jpg'],
        ['title' => 'Flutter', 'description' => 'For Web Development', 'link' => '', 'background' => 'images/bg/bg1.jpg'],
        ['title' => 'Graphics Design', 'description' => 'Adobe packages for Designing, Mockups, poster and many more.', 'link' => '', 'background' => 'images/bg/bg.jpg'],
        ['title' => 'SEO', 'description' => 'For website Optimization and Ranking', 'link' => '', 'background' => 'images/bg/it.jpg'],
        ['title' => 'Content Copy Writing', 'description' => 'Content for Your websites', 'link' => '', 'background' => 'images/bg/bg1.jpg'],
    ],

    'services' => [
        ['title' => 'Web Development', 'link' => ''],
        ['title' => 'WordPress Website Development', 'link' => ''],
        ['title' => 'UI/UX Design', 'link' => ''],
        ['title' => 'Digital Marketing', 'link' => ''],
        ['title' => 'Graphics Design', 'link' => ''],
        ['title' => 'Social Media Manager', 'link' => ''],
    ],

    'resume' => [
        ['column' => 'left', 'title' => 'University', 'place' => 'Tribhuvan University of Nepal — Asian College Of Higher Studies', 'date' => 'Jan 2016 - Dec 2021', 'text' => 'Completed my Bachelor Degree in Bachelor of Science in Computer Science of Information Technology(BSc. CSIT)'],
        ['column' => 'left', 'title' => 'College/High School', 'place' => 'Kathmandu BernHardt College, Kathmandu', 'date' => 'Jan 2014 - Dec 2016', 'text' => 'Completed my +2 from Science Stream.'],
        ['column' => 'left', 'title' => 'School Leaving Certificate (SLC)', 'place' => 'Montessori Secondary School , Ilam', 'date' => 'Completed Schooling on Jan 2014', 'text' => ''],
        ['column' => 'projects', 'title' => 'Project: Review Website', 'place' => 'Ratedisco', 'date' => 'Jan 2023 - April 2023', 'text' => 'Created a website for review.'],
        ['column' => 'projects', 'title' => 'Project: Movie Recommendation System', 'place' => 'Final year Project', 'date' => 'Dec 2019 - April 2020', 'text' => 'Using Python and Django'],
        ['column' => 'right', 'title' => 'Digital Marketing', 'place' => 'Prizma Digital Pvt. Ltd.', 'date' => 'Jan 2021 - Dec 2022', 'text' => "My basic roles and responsibilities as a Digital Marketing intern was to Data analysis , Content Creator, SEO , Web Development , Communication Skills , Social Media and Intermediate Designing Skills\n• was able to gain audience with a Marketing strategies"],
        ['column' => 'right', 'title' => 'Website Development', 'place' => '', 'date' => 'Jan 2020 - and so on', 'text' => "• 20-hour Frontend Development using React\n• Good knowledge Html Css Js\n• Python Django For backend"],
        ['column' => 'right', 'title' => 'WordPress Development', 'place' => '', 'date' => 'Jan 2019 - and so on', 'text' => "• Customize wordpress website\n• Good knowledge Html Css Js"],
        ['column' => 'right', 'title' => 'Graphics Designing', 'place' => '', 'date' => '2019 - and so on', 'text' => "• Can create Social Media poster\n• Mockups\n• Logo Designing\n• Content Creator"],
        ['column' => 'right', 'title' => 'UI/UX Designing', 'place' => '', 'date' => '2021 - and so on', 'text' => "• Can Create landing page for website landing page\n• UI for WebApp\n• UX experience for UI design\n• Application used (Figma, Adobe xd, adobe product)"],
    ],

    'gallery' => $gallery,

    'projects' => [
        [
            'category' => 'WordPress', 'heading' => 'WordPress Website Development', 'name' => 'Ultra Led Display',
            'description' => 'The Ultra Led Display Pvt. Ltd. is established in 2014 under the group of Himalayan Solution. Ultra Led Display is one of the pioneers in LED Display Boards Solution in Nepal.',
            'background' => 'images/bg/wordpress1.jpg', 'logo' => 'images/bg/Untitled-2.png',
            'client' => 'Ultra Led Display Nepal', 'completion' => 'February 5th, 2019',
            'type' => 'WordPress Website Development', 'services' => $wpServices,
            'link_label' => 'Ultraled display', 'link_url' => 'https://ultraleddisplay.com/',
        ],
        [
            'category' => 'WordPress', 'heading' => 'WordPress Website Development', 'name' => 'Parijat Clinic', 'description' => $wpText,
            'background' => 'images/bg/bg222.jpg', 'logo' => 'images/bg/parijat.png',
            'client' => 'Parijat Clinic', 'completion' => '2019',
            'type' => 'WordPress Website Development', 'services' => $wpServices,
            'link_label' => 'Parijat Clinic', 'link_url' => 'http://parijatclinic.com.np/',
        ],
        [
            'category' => 'WordPress', 'heading' => 'WordPress Website Development', 'name' => 'Sumeru City Hospital', 'description' => $wpText,
            'background' => 'images/bg/bg333.jpg', 'logo' => 'images/bg/sumeru.png',
            'client' => 'Sumeru City Hospital', 'completion' => '2020',
            'type' => 'WordPress Website Development', 'services' => $wpServices,
            'link_label' => 'Sumeru City Hospital', 'link_url' => 'https://sumerucityhospital.com/',
        ],
        [
            'category' => 'WordPress', 'heading' => 'WordPress Website Development', 'name' => 'Foodial Nepal', 'description' => $wpText,
            'background' => 'images/bg/wordpress1.jpg', 'logo' => 'images/bg/foodial.png',
            'client' => 'Foodial Nepal', 'completion' => '2021',
            'type' => 'WordPress Website Development', 'services' => 'Digital Marketing ( SEO, Graphics Designing, Content creation )',
            'link_label' => 'Foodial Nepal', 'link_url' => 'https://foodialnepal.com/',
        ],
    ],
];
