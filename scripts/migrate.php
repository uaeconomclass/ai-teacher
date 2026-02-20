<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Database\Database;
use App\Database\Migrations\CreateDialogueMessagesTable;
use App\Database\Migrations\CreateDialoguesTable;
use App\Database\Migrations\CreateGrammarTopicsTable;
use App\Database\Migrations\CreateLessonsTable;
use App\Database\Migrations\CreateLevelsTable;
use App\Database\Migrations\CreateTopicsTable;
use App\Database\Migrations\CreateUserProgressTable;
use App\Database\Migrations\CreateUsersTable;
use App\Database\Migrations\MigrationInterface;

$pdo = Database::pdo();

/** @var MigrationInterface[] $migrations */
$migrations = [
    new CreateUsersTable(),
    new CreateLevelsTable(),
    new CreateTopicsTable(),
    new CreateGrammarTopicsTable(),
    new CreateLessonsTable(),
    new CreateDialoguesTable(),
    new CreateDialogueMessagesTable(),
    new CreateUserProgressTable(),
];

echo "Running migrations...\n";

foreach ($migrations as $migration) {
    $pdo->exec($migration->up());
    echo "[OK] {$migration->name()}\n";
}

$pdo->exec(<<<SQL
INSERT IGNORE INTO levels (id, code, title) VALUES
  (1, 'A1', 'Beginner'),
  (2, 'A2', 'Elementary'),
  (3, 'B1', 'Intermediate'),
  (4, 'B2', 'Upper Intermediate'),
  (5, 'C1', 'Advanced'),
  (6, 'C2', 'Proficient');
SQL);

echo "[OK] seed_levels\n";

$topicSeed = [
    'A1' => [
        'Introductions',
        'Alphabet and Spelling',
        'Numbers Time and Dates',
        'Family and Friends',
        'Home and Rooms',
        'Daily Routine',
        'School Essentials',
        'Basic Jobs',
        'Food and Drinks',
        'Shopping Basics',
        'Clothes and Colors',
        'Weather and Seasons',
        'City Directions',
        'Transport Basics',
        'Hobbies and Free Time',
        'Health Basics',
        'Phone and Technology Basics',
        'Holidays and Birthdays',
    ],
    'A2' => [
        'Travel Plans',
        'Airport and Hotel',
        'Health and Appointments',
        'Work Day',
        'Housing and Services',
        'Past Weekend Stories',
        'Restaurant and Complaints',
        'Public Services',
        'Education and Courses',
        'Social Media Habits',
        'Money and Banking',
        'Shopping Decisions',
        'Daily Problems and Solutions',
        'Festivals and Traditions',
        'Fitness and Lifestyle',
        'Movies and Entertainment',
        'Future Plans',
        'Neighbor and Community',
        'Emergency Situations',
        'Simple Opinions and Preferences',
    ],
    'B1' => [
        'Career Goals',
        'Job Interviews',
        'Workplace Communication',
        'Relationships',
        'Media Habits',
        'Problem Solving',
        'Culture and Lifestyle',
        'Travel Incidents',
        'Learning Strategies',
        'Stress and Wellbeing',
        'Consumer Decisions',
        'Technology in Daily Life',
        'Environment in My City',
        'Friendship and Conflict',
        'Personal Finance',
        'News and Current Events',
        'Personal Growth',
        'Decision Making',
        'Plans and Priorities',
        'Giving Advice and Support',
    ],
    'B2' => [
        'Business Talks',
        'Negotiation Skills',
        'Technology and Society',
        'Environment Debate',
        'Education Systems',
        'Leadership in Teams',
        'Project Management',
        'Conflict Resolution',
        'Remote Work and Productivity',
        'Marketing and Branding',
        'Ethical Dilemmas',
        'Career Development',
        'Public Policy Discussion',
        'Digital Privacy',
        'Media Influence',
        'Innovation Cases',
        'Data and Evidence',
        'Cross Cultural Collaboration',
        'Persuasion Techniques',
        'Argument and Counterargument',
    ],
    'C1' => [
        'Leadership',
        'Ethics and Decisions',
        'Innovation',
        'Intercultural Communication',
        'Public Speaking',
        'Strategic Thinking',
        'Organizational Change',
        'Risk and Uncertainty',
        'Economics and Markets',
        'Globalization',
        'Policy Analysis',
        'Research Communication',
        'Media Narratives',
        'Philosophy in Practice',
        'Law and Society',
        'Science and Responsibility',
        'Advanced Negotiation',
        'High Stakes Meetings',
        'Narrative Framing',
        'Mentoring and Coaching',
    ],
    'C2' => [
        'Policy and Society',
        'Advanced Debate',
        'Expert Domains',
        'Rhetoric and Persuasion',
        'Nuance and Tone',
        'Geopolitics',
        'Macroeconomic Narratives',
        'Epistemology and Truth',
        'Ethics of Technology',
        'Judicial Reasoning',
        'Crisis Communication',
        'Academic Defense',
        'Executive Communication',
        'Media Manipulation',
        'Cultural Subtext',
        'Humor Irony and Sarcasm',
        'Philosophical Argumentation',
        'Multilayered Negotiation',
        'Public Intellectual Discussion',
        'Precision Under Pressure',
    ],
];

$topicStmt = $pdo->prepare(
    'INSERT IGNORE INTO topics (level_id, slug, title, position)
     VALUES (
       (SELECT id FROM levels WHERE code = :level_code LIMIT 1),
       :slug,
       :title,
       :position
     )'
);

foreach ($topicSeed as $levelCode => $titles) {
    $position = 1;
    foreach ($titles as $title) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        $topicStmt->execute([
            'level_code' => $levelCode,
            'slug' => strtolower($levelCode) . '-' . $slug,
            'title' => $title,
            'position' => $position,
        ]);
        $position++;
    }
}

echo "[OK] seed_topics\n";

$grammarSeed = [
    'A1' => [
        'To be',
        'Subject Pronouns',
        'Possessive Adjectives',
        'Articles a/an/the',
        'Singular and Plural Nouns',
        'This That These Those',
        'Present Simple',
        'Present Simple Questions',
        'Present Simple Negatives',
        'Frequency Adverbs',
        'There is and There are',
        'Some and Any',
        'Can and Can not',
        'Imperatives',
        'Basic Prepositions of Place',
        'Object Pronouns',
        'Possessive s',
        'Basic Question Words',
    ],
    'A2' => [
        'Past Simple Regular and Irregular',
        'Past Simple Questions and Negatives',
        'Was and Were',
        'Going to Future',
        'Will Future',
        'Present Continuous',
        'Present Continuous for Future',
        'Comparatives',
        'Superlatives',
        'Countable and Uncountable Nouns',
        'Much Many A lot of',
        'Few and Little',
        'Too and Enough',
        'Have to and Must',
        'Should and Should not',
        'Could for Requests',
        'Basic Relative Clauses',
        'Time Clauses',
    ],
    'B1' => [
        'Present Perfect',
        'Present Perfect vs Past Simple',
        'Present Perfect with For and Since',
        'Past Continuous',
        'Past Continuous vs Past Simple',
        'First Conditional',
        'Second Conditional',
        'Modal Verbs of Advice and Obligation',
        'Modal Verbs of Possibility',
        'Gerund and Infinitive',
        'Reported Speech Basics',
        'Question Tags',
        'Defining Relative Clauses',
        'Linking Words for Reason and Contrast',
        'Used to',
        'So and Such',
        'Reflexive Pronouns',
        'Basic Passive Voice',
    ],
    'B2' => [
        'Past Perfect',
        'Present Perfect Continuous',
        'Past Perfect Continuous',
        'Future Perfect',
        'Future Continuous',
        'Passive Voice in Multiple Tenses',
        'Reported Speech Advanced',
        'Third Conditional',
        'Mixed Conditionals',
        'Modal Verbs for Deduction',
        'Past Modals',
        'Wish and If only',
        'Causative Have and Get',
        'Non Defining Relative Clauses',
        'Participle Clauses',
        'Complex Linking Devices',
        'Inversion for Emphasis',
        'Hedging Expressions',
    ],
    'C1' => [
        'Advanced Clause Structures',
        'Inversion with Negative Adverbials',
        'Cleft and Pseudo Cleft Sentences',
        'Nominalization',
        'Advanced Hedging Language',
        'Discourse Markers for Argumentation',
        'Modal Nuance',
        'Advanced Conditionals',
        'Subjunctive Patterns',
        'Ellipsis and Substitution',
        'Fronting and Emphasis',
        'Advanced Passive Constructions',
        'Precision with Articles and Determiners',
        'Complex Relative Clauses',
        'Formal Register Grammar',
        'Stance and Evaluation Language',
        'Advanced Reporting Verbs',
        'Parallel Structures',
    ],
    'C2' => [
        'Rhetorical Structures',
        'Nuanced Modality',
        'Register Shifting',
        'Complex Conditionals',
        'Advanced Cohesion',
        'Metadiscourse Control',
        'Pragmatic Softening and Facework',
        'Precision in Reference Tracking',
        'Advanced Ellipsis in Speech',
        'Idiomatic Grammar Patterns',
        'High Density Information Packaging',
        'Subtle Inversion and Fronting',
        'Irony and Distancing Structures',
        'Discourse Framing at Scale',
        'Advanced Concession Structures',
        'Rhetorical Questioning',
        'Academic and Executive Syntax Switching',
        'Argument Architecture',
    ],
];

$grammarStmt = $pdo->prepare(
    'INSERT IGNORE INTO grammar_topics (level_id, slug, title, position)
     VALUES (
       (SELECT id FROM levels WHERE code = :level_code LIMIT 1),
       :slug,
       :title,
       :position
     )'
);

foreach ($grammarSeed as $levelCode => $titles) {
    $position = 1;
    foreach ($titles as $title) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        $grammarStmt->execute([
            'level_code' => $levelCode,
            'slug' => strtolower($levelCode) . '-' . $slug,
            'title' => $title,
            'position' => $position,
        ]);
        $position++;
    }
}

echo "[OK] seed_grammar_topics\n";
echo "Migrations completed.\n";
