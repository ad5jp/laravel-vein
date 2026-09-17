<?php

declare(strict_types=1);

namespace AD5jp\Vein\Auth;

use Illuminate\Database\Eloquent\Model;

/**
 * 管理者が自分を締め出すのを止める。
 *
 * ログインできるモデルをノードにすると、一覧から自分を消せてしまう。最後の 1 人を
 * 消せば誰も入れなくなり、戻すには seeder か tinker が要る。**ノードとして消せる
 * ようにしたのは vein なので、止めるのも vein が持つ。**
 *
 * 利用側のモデルに書かせると、導入先ごとに同じ関門を書き写すことになり、
 * 書き忘れても誰も気づかない。
 */
class LockoutGuard
{
    /**
     * 消せない理由。消してよければ null。
     */
    public static function reasonToKeep(Model $record): ?string
    {
        if (! self::isSignInModel($record)) {
            return null;
        }

        if (self::isSelf($record)) {
            return '自分のアカウントは削除できません。';
        }

        if (self::isLastOne($record)) {
            return 'ログインできる人が居なくなるため削除できません。';
        }

        return null;
    }

    /**
     * その行が、いまログインしている本人か。
     *
     * 一覧で「これは自分」と示すために使う。消せない理由（reasonToKeep）とは分ける。
     * 最後の 1 人も消せないが、それは「自分」ではない。
     */
    public static function isCurrentUser(Model $record): bool
    {
        return self::isSignInModel($record) && self::isSelf($record);
    }

    private static function isSignInModel(Model $record): bool
    {
        $model = AdminGuard::model();

        return $model !== null && $record::class === $model;
    }

    private static function isSelf(Model $record): bool
    {
        $id = AdminGuard::guard()->id();

        // 緩く比べる。ルートから来た id は文字列、モデルの主キーは整数になりうる
        return $id !== null && (string) $record->getKey() === (string) $id;
    }

    /**
     * 最後の 1 人か。
     *
     * 消してから戻す（トランザクションで巻き戻す）案もあるが、削除は子レコードと
     * ファイルまで巻き込む。戻せるかを実装の細部に賭ける理由が無いので、先に数える。
     *
     * 数えてから消すまでの間に別の人が消す可能性は残るが、管理者が数名の利用では
     * 起こらないとしてロックは取らない。
     */
    private static function isLastOne(Model $record): bool
    {
        return $record->newQuery()->toBase()->count() <= 1;
    }
}
